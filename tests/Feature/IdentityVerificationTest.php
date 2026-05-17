<?php

namespace Tests\Feature;

use App\Models\NationalId;
use App\Models\PendingRegistration;
use App\Models\Role;
use App\Models\User;
use App\Services\IdentityOcrService;
use App\Services\LebaneseNationalIdParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $adminRole = Role::create(['role' => 'admin']);
        Role::create(['role' => 'citizen']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);
    }

    public function test_registration_with_new_national_id_does_not_create_user_immediately(): void
    {
        $this->fakeOcr();

        $this->post(route('register'), $this->registrationPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Your registration is pending admin ID verification.');

        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'citizen@example.com',
            'status' => PendingRegistration::STATUS_PENDING_REVIEW,
        ]);
        $this->assertDatabaseHas('national_ids', [
            'national_id_number_normalized' => '123456789',
            'first_name_ar' => 'ثريا',
            'family_name_ar' => 'محمد',
            'status' => NationalId::STATUS_PENDING_REVIEW,
        ]);
    }

    public function test_registration_with_duplicate_national_id_does_not_create_records(): void
    {
        $this->fakeOcr();

        NationalId::create([
            'national_id_number' => '123456789/',
            'national_id_number_normalized' => '123456789',
            'status' => NationalId::STATUS_PENDING_REVIEW,
        ]);

        $this->post(route('register'), $this->registrationPayload())
            ->assertSessionHasErrors('id_image');

        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'citizen@example.com']);
        $this->assertSame(1, NationalId::where('national_id_number_normalized', '123456789')->count());
    }

    public function test_registration_with_duplicate_pending_email_is_blocked(): void
    {
        PendingRegistration::create([
            'name' => 'Existing Pending',
            'email' => 'citizen@example.com',
            'password' => Hash::make('password123'),
            'status' => PendingRegistration::STATUS_PENDING_REVIEW,
        ]);

        $this->post(route('register'), $this->registrationPayload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('national_ids', ['national_id_number_normalized' => '123456789']);
    }

    public function test_admin_can_approve_pending_id_and_user_is_created(): void
    {
        $this->fakeOcr();
        $this->post(route('register'), $this->registrationPayload());

        $nationalId = NationalId::firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.approve', $nationalId), [
                'admin_notes' => 'Looks valid.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $nationalId));

        $user = User::where('email', 'citizen@example.com')->firstOrFail();

        $this->assertTrue((bool) $user->is_active);
        $this->assertSame('active', $user->status);
        $this->assertSame($user->id, $nationalId->fresh()->uploaded_by);
        $this->assertSame(NationalId::STATUS_APPROVED, $nationalId->fresh()->status);
        $this->assertSame(PendingRegistration::STATUS_APPROVED, $nationalId->pendingRegistration->fresh()->status);
    }

    public function test_admin_can_reject_and_user_is_not_created(): void
    {
        $this->fakeOcr();
        $this->post(route('register'), $this->registrationPayload());

        $nationalId = NationalId::firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.reject', $nationalId), [
                'admin_notes' => 'Image is unclear.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $nationalId));

        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
        $this->assertSame(NationalId::STATUS_REJECTED, $nationalId->fresh()->status);
        $this->assertSame(PendingRegistration::STATUS_REJECTED, $nationalId->pendingRegistration->fresh()->status);
    }

    public function test_arabic_ocr_parser_extracts_lebanese_id_fields(): void
    {
        $fields = app(LebaneseNationalIdParser::class)->parse($this->arabicOcrText());

        $this->assertSame('ثريا', $fields['first_name_ar']);
        $this->assertSame('محمد', $fields['family_name_ar']);
        $this->assertSame('جورج', $fields['father_name_ar']);
        $this->assertSame('ماري', $fields['mother_name_ar']);
        $this->assertSame('بيروت', $fields['place_of_birth_ar']);
        $this->assertSame('1/2/1999', $fields['date_of_birth_text']);
        $this->assertSame('123456789/', $fields['national_id_number']);
        $this->assertSame('123456789', $fields['national_id_number_normalized']);
    }

    public function test_arabic_digits_are_normalized(): void
    {
        $fields = app(LebaneseNationalIdParser::class)->parse("الاسم: ثريا\nالشهرة محمد\n١٢٣٤٥٦٧٨٩/");

        $this->assertSame('123456789', $fields['national_id_number_normalized']);
    }

    public function test_missing_national_id_number_goes_to_pending_review(): void
    {
        $this->fakeOcr("وزارة الداخلية\nالاسم: ثريا\nالشهرة محمد");

        $this->post(route('register'), $this->registrationPayload())
            ->assertRedirect(route('login'));

        $nationalId = NationalId::firstOrFail();

        $this->assertSame(NationalId::STATUS_PENDING_REVIEW, $nationalId->status);
        $this->assertNull($nationalId->national_id_number_normalized);
        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
    }

    public function test_admin_review_page_shows_extracted_national_id_data(): void
    {
        $this->fakeOcr();
        $this->post(route('register'), $this->registrationPayload());

        $nationalId = NationalId::firstOrFail();
        Storage::disk('public')->assertExists($nationalId->id_image_path);

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.show', $nationalId))
            ->assertOk()
            ->assertSee('ثريا')
            ->assertSee('محمد')
            ->assertSee('123456789')
            ->assertSee('Raw OCR Text');
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Citizen User',
            'email' => 'citizen@example.com',
            'phone' => '70000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'id_image' => $this->fakePng('id-card.png'),
        ], $overrides);
    }

    private function fakeOcr(?string $text = null): void
    {
        $text ??= $this->arabicOcrText();

        $this->app->bind(IdentityOcrService::class, function () use ($text) {
            return new class($text) extends IdentityOcrService {
                public function __construct(private string $text)
                {
                }

                public function analyze(string $diskPath): array
                {
                    return [
                        'success' => filled($this->text),
                        'confidence' => filled($this->text) ? 0.91 : 0,
                        'text' => $this->text,
                        'fields' => [],
                        'raw' => ['driver' => 'google-cloud-vision'],
                    ];
                }
            };
        });
    }

    private function arabicOcrText(): string
    {
        return "وزارة الداخلية\nالاسم: ثريا\nالشهرة محمد\nاسم الاب جورج\nاسم الام ماري\nمحل الولادة بيروت\nتاريخ الولادة : 1/2/1999\n123456789/\nبطاقة هوية";
    }

    private function fakePng(string $name): \Illuminate\Http\Testing\File
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
