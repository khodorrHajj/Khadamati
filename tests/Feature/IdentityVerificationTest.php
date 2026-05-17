<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\NationalId;
use App\Models\PendingRegistration;
use App\Models\Role;
use App\Models\User;
use App\Services\IdentityImageInspectionService;
use App\Services\IdentityOcrService;
use App\Services\LebaneseNationalIdParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
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

        $this->fakeIdentityServices();
    }

    public function test_registration_with_new_national_id_does_not_create_user_immediately(): void
    {
        $this->fakeSignupOcr();
        $expectedFields = app(LebaneseNationalIdParser::class)->parse(
            $this->frontArabicOcrText(),
            $this->backArabicOcrText()
        );

        $this->post(route('register'), $this->registrationPayload())
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Your registration was submitted. ID OCR processing will continue in the background before admin review.');

        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
        $this->assertDatabaseHas('pending_registrations', [
            'email' => 'citizen@example.com',
            'status' => PendingRegistration::STATUS_PENDING_REVIEW,
        ]);
        $this->assertDatabaseHas('national_ids', [
            'national_id_number_normalized' => $expectedFields['national_id_number_normalized'],
            'first_name_ar' => $expectedFields['first_name_ar'],
            'family_name_ar' => $expectedFields['family_name_ar'],
            'mother_family_name_ar' => $expectedFields['mother_family_name_ar'],
            'gender_ar' => $expectedFields['gender_ar'],
            'marital_status_ar' => $expectedFields['marital_status_ar'],
            'record_number' => $expectedFields['record_number'],
            'blood_type' => $expectedFields['blood_type'],
            'issue_date_text' => $expectedFields['issue_date_text'],
            'status' => NationalId::STATUS_PENDING_REVIEW,
        ]);

        $nationalId = NationalId::firstOrFail();
        Storage::disk('public')->assertExists($nationalId->id_image_path);
        Storage::disk('public')->assertExists($nationalId->id_image_back_path);
    }

    public function test_registration_with_duplicate_national_id_does_not_create_records(): void
    {
        $this->fakeSignupOcr();
        $expectedFields = app(LebaneseNationalIdParser::class)->parse(
            $this->frontArabicOcrText(),
            $this->backArabicOcrText()
        );

        NationalId::create([
            'national_id_number' => '123456789/',
            'national_id_number_normalized' => $expectedFields['national_id_number_normalized'],
            'status' => NationalId::STATUS_PENDING_REVIEW,
        ]);

        $this->post(route('register'), $this->registrationPayload())
            ->assertSessionHasErrors('id_image_front');

        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'citizen@example.com']);
        $this->assertSame(1, NationalId::where('national_id_number_normalized', $expectedFields['national_id_number_normalized'])->count());
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

        $this->assertDatabaseCount('national_ids', 0);
    }

    public function test_admin_queue_shows_identity_verification_records_created_from_citizen_upload(): void
    {
        $citizen = $this->createInactiveCitizen();
        $verification = $this->submitIdentityVerification($citizen);

        $this->assertSame('جورج', $verification->extracted_father_name);
        $this->assertSame('أنثى', $verification->extracted_gender);
        $this->assertSame('5/4/2024', $verification->extracted_issue_date_text);

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.index'))
            ->assertOk()
            ->assertSee($citizen->name)
            ->assertSee($citizen->email)
            ->assertSee('Needs Review');
    }

    public function test_admin_can_approve_identity_verification_and_activate_citizen(): void
    {
        $citizen = $this->createInactiveCitizen();
        $verification = $this->submitIdentityVerification($citizen);

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.approve', $verification), [
                'admin_notes' => 'Looks valid.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $verification));

        $this->assertTrue((bool) $citizen->fresh()->is_active);
        $this->assertSame('active', $citizen->fresh()->status);
        $this->assertSame(IdentityVerification::STATUS_APPROVED, $verification->fresh()->status);
        $this->assertSame($this->admin->id, $verification->fresh()->reviewed_by);
    }

    public function test_admin_can_reject_identity_verification_and_keep_citizen_inactive(): void
    {
        $citizen = $this->createInactiveCitizen();
        $verification = $this->submitIdentityVerification($citizen);

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.reject', $verification), [
                'admin_notes' => 'Image is unclear.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $verification));

        $this->assertFalse((bool) $citizen->fresh()->is_active);
        $this->assertSame('inactive', $citizen->fresh()->status);
        $this->assertSame(IdentityVerification::STATUS_REJECTED, $verification->fresh()->status);
        $this->assertSame('Image is unclear.', $verification->fresh()->admin_notes);
    }

    public function test_citizen_waiting_page_shows_processing_message_for_inactive_user(): void
    {
        $citizen = $this->createInactiveCitizen();
        $verification = IdentityVerification::create([
            'user_id' => $citizen->id,
            'status' => IdentityVerification::STATUS_PROCESSING,
        ]);

        $this->actingAs($citizen)
            ->get(route('identity.verification.create'))
            ->assertOk()
            ->assertSee('Your ID is getting processed.')
            ->assertSee(route('identity.verification.status'), false)
            ->assertDontSee('Go to Dashboard');
    }

    public function test_identity_verification_waiting_status_endpoint_redirects_after_approval(): void
    {
        $citizen = $this->createInactiveCitizen();
        IdentityVerification::create([
            'user_id' => $citizen->id,
            'status' => IdentityVerification::STATUS_APPROVED,
        ]);
        $citizen->update([
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->actingAs($citizen)
            ->getJson(route('identity.verification.status'))
            ->assertOk()
            ->assertJson([
                'status' => IdentityVerification::STATUS_APPROVED,
                'is_active' => true,
                'should_redirect' => true,
                'redirect_url' => route('citizen.dashboard'),
            ]);
    }

    public function test_approved_citizen_skips_identity_verification_page(): void
    {
        $citizen = $this->createInactiveCitizen();
        IdentityVerification::create([
            'user_id' => $citizen->id,
            'status' => IdentityVerification::STATUS_APPROVED,
        ]);
        $citizen->update([
            'is_active' => true,
            'status' => 'active',
        ]);

        $this->actingAs($citizen)
            ->get(route('identity.verification.create'))
            ->assertRedirect(route('citizen.dashboard'));
    }

    public function test_arabic_ocr_parser_extracts_front_and_back_lebanese_id_fields(): void
    {
        $fields = app(LebaneseNationalIdParser::class)->parse(
            $this->frontArabicOcrText(),
            $this->backArabicOcrText()
        );

        $this->assertSame('ثريا', $fields['first_name_ar']);
        $this->assertSame('محمد', $fields['family_name_ar']);
        $this->assertSame('جورج', $fields['father_name_ar']);
        $this->assertSame('ماري', $fields['mother_name_ar']);
        $this->assertSame('خوري', $fields['mother_family_name_ar']);
        $this->assertSame('بيروت', $fields['place_of_birth_ar']);
        $this->assertSame('1/2/1999', $fields['date_of_birth_text']);
        $this->assertSame('123456789/', $fields['national_id_number']);
        $this->assertSame('123456789', $fields['national_id_number_normalized']);
        $this->assertSame('أنثى', $fields['gender_ar']);
        $this->assertSame('عزباء', $fields['marital_status_ar']);
        $this->assertSame('456', $fields['record_number']);
        $this->assertSame('الأشرفية', $fields['locality_ar']);
        $this->assertSame('بيروت', $fields['governorate_ar']);
        $this->assertSame('بيروت', $fields['district_ar']);
        $this->assertSame('AB+', $fields['blood_type']);
        $this->assertSame('5/4/2024', $fields['issue_date_text']);
    }

    public function test_arabic_digits_are_normalized(): void
    {
        $fields = app(LebaneseNationalIdParser::class)->parse(
            "الاسم: ثريا\nتاريخ الولادة: ١/٢/١٩٩٩\n١٢٣٤٥٦٧٨٩/",
            "رقم السجل: ٤٥٦"
        );

        $this->assertSame('123456789', $fields['national_id_number_normalized']);
        $this->assertSame('456', $fields['record_number']);
    }

    public function test_parser_handles_real_ocr_style_mother_label_and_year_first_birth_date(): void
    {
        $fields = app(LebaneseNationalIdParser::class)->parse(
            "الجمهورية اللبنانية\nوزارة الداخلية\nالاسم: خضر\nالشهرة: الحاج موسى\nاسم الاب : حسن\nاسم الام وشهرها : ميساء الموسوي\nمحل الولادة: حارة حريك\nتاريخ الولادة : ٢٠٠٥/٠٥/٢٩\n٠٠٠٠٧٤٦٩١٥٢٧\nبطاقة هوية",
            "الجنس: ذكر\nالوضع العائلي : أعزب\nرقم السجل : ١٦٩\nالمحلة أو القرية: حارة حريك\nالمحافظة جبل لبنان\nالقضاء بعبدا\nتاريخ الإصدار: ۲۰۲۲/۰۲/۲۲\nفئة الدم: +A"
        );

        $this->assertSame('ميساء', $fields['mother_name_ar']);
        $this->assertSame('الموسوي', $fields['mother_family_name_ar']);
        $this->assertSame('2005/05/29', $fields['date_of_birth_text']);
        $this->assertSame('000074691527', $fields['national_id_number']);
        $this->assertSame('000074691527', $fields['national_id_number_normalized']);
        $this->assertSame('2022/02/22', $fields['issue_date_text']);
        $this->assertSame('A+', $fields['blood_type']);
    }

    public function test_identity_verification_stores_year_first_birth_date_and_normalized_blood_type(): void
    {
        $this->bindSequentialOcr([
            [
                'success' => true,
                'confidence' => 0.93,
                'text' => "الجمهورية اللبنانية\nوزارة الداخلية\nالاسم: خضر\nالشهرة: الحاج موسى\nاسم الاب : حسن\nاسم الام وشهرها : ميساء الموسوي\nمحل الولادة: حارة حريك\nتاريخ الولادة : ٢٠٠٥/٠٥/٢٩\n٠٠٠٠٧٤٦٩١٥٢٧\nبطاقة هوية",
                'fields' => [],
                'raw' => ['driver' => 'google-cloud-vision', 'side' => 'front'],
            ],
            [
                'success' => true,
                'confidence' => 0.89,
                'text' => "الجنس: ذكر\nالوضع العائلي : أعزب\nرقم السجل : ١٦٩\nالمحلة أو القرية: حارة حريك\nالمحافظة جبل لبنان\nالقضاء بعبدا\nتاريخ الإصدار: ۲۰۲۲/۰۲/۲۲\nفئة الدم: +A",
                'fields' => [],
                'raw' => ['driver' => 'google-cloud-vision', 'side' => 'back'],
            ],
        ]);

        $citizen = $this->createInactiveCitizen();
        $verification = $this->submitIdentityVerification($citizen);

        $this->assertSame('2005-05-29', optional($verification->extracted_date_of_birth)->format('Y-m-d'));
        $this->assertSame('A+', $verification->extracted_blood_type);
    }

    public function test_missing_national_id_number_goes_to_pending_review(): void
    {
        $this->fakeSignupOcr(
            "وزارة الداخلية\nالاسم: ثريا\nالشهرة محمد\nاسم الأب جورج\nاسم الأم وشهرتها ماري خوري\nمحل الولادة بيروت\nتاريخ الولادة: 1/2/1999",
            $this->backArabicOcrText()
        );

        $this->post(route('register'), $this->registrationPayload())
            ->assertRedirect(route('login'));

        $nationalId = NationalId::firstOrFail();

        $this->assertSame(NationalId::STATUS_PENDING_REVIEW, $nationalId->status);
        $this->assertNull($nationalId->national_id_number_normalized);
        $this->assertSame('أنثى', $nationalId->gender_ar);
        $this->assertSame('5/4/2024', $nationalId->issue_date_text);
        $this->assertDatabaseMissing('users', ['email' => 'citizen@example.com']);
    }

    public function test_admin_review_page_shows_identity_verification_data_and_both_images(): void
    {
        $citizen = $this->createInactiveCitizen();
        $verification = $this->submitIdentityVerification($citizen);

        Storage::disk('public')->assertExists($verification->id_image_path);
        Storage::disk('public')->assertExists($verification->id_image_back_path);

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.show', $verification))
            ->assertOk()
            ->assertSee('Citizen User')
            ->assertSee('Open Front Image')
            ->assertSee('Open Back Image')
            ->assertSee('جورج')
            ->assertSee('عزباء')
            ->assertSee('AB+')
            ->assertSee('5/4/2024');

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.image', $verification))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.image', [$verification, 'side' => 'back']))
            ->assertOk();
    }

    public function test_admin_queue_filters_and_search_identity_verifications(): void
    {
        $firstCitizen = $this->createInactiveCitizen([
            'name' => 'Alpha Citizen',
            'email' => 'alpha@example.com',
        ]);
        $secondCitizen = $this->createInactiveCitizen([
            'name' => 'Beta Citizen',
            'email' => 'beta@example.com',
        ]);

        $this->submitIdentityVerification($firstCitizen);
        $secondVerification = $this->submitIdentityVerification($secondCitizen);
        $secondVerification->update([
            'status' => IdentityVerification::STATUS_REJECTED,
            'admin_notes' => 'Rejected for review.',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.index', [
                'status' => IdentityVerification::STATUS_REJECTED,
                'search' => 'beta@example.com',
            ]))
            ->assertOk()
            ->assertSee('Beta Citizen')
            ->assertDontSee('Alpha Citizen');
    }

    public function test_admin_review_page_renders_correct_arabic_labels(): void
    {
        $citizen = $this->createInactiveCitizen();
        $verification = $this->submitIdentityVerification($citizen);

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.show', $verification))
            ->assertOk()
            ->assertSee('الاسم')
            ->assertSee('شهرة الأم')
            ->assertSee('فئة الدم')
            ->assertSee('تاريخ الإصدار')
            ->assertSee('الوضع العائلي')
            ->assertSee('المحلة أو القرية');
    }

    private function createInactiveCitizen(array $attributes = []): User
    {
        $citizenRoleId = Role::where('role', 'citizen')->value('id');

        return User::create(array_merge([
            'name' => 'Citizen User',
            'email' => 'citizen@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $citizenRoleId,
            'status' => 'inactive',
            'is_active' => false,
            'two_factor_enabled' => false,
        ], $attributes));
    }

    private function submitIdentityVerification(User $citizen): IdentityVerification
    {
        $this->actingAs($citizen)
            ->post(route('identity.verification.store'), [
                'id_image_front' => $this->fakePng('citizen-id-front.png'),
                'id_image_back' => $this->fakePng('citizen-id-back.png'),
            ])
            ->assertRedirect(route('identity.verification.create'));

        return $citizen->fresh()->latestIdentityVerification()->firstOrFail();
    }

    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Citizen User',
            'email' => 'citizen@example.com',
            'phone' => '70000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'id_image_front' => $this->fakePng('id-card-front.png'),
            'id_image_back' => $this->fakePng('id-card-back.png'),
        ], $overrides);
    }

    private function fakeSignupOcr(?string $frontText = null, ?string $backText = null): void
    {
        $frontText ??= $this->frontArabicOcrText();
        $backText ??= $this->backArabicOcrText();

        $lookupResponse = [
            'success' => filled($frontText),
            'confidence' => filled($frontText) ? 0.93 : 0,
            'text' => $frontText,
            'fields' => [],
            'raw' => ['driver' => 'google-cloud-vision', 'side' => 'front_lookup'],
        ];

        $this->bindSequentialOcr([
            [
                'success' => filled($frontText),
                'confidence' => filled($frontText) ? 0.93 : 0,
                'text' => $frontText,
                'fields' => [],
                'raw' => ['driver' => 'google-cloud-vision', 'side' => 'front'],
            ],
            [
                'success' => filled($backText),
                'confidence' => filled($backText) ? 0.89 : 0,
                'text' => $backText,
                'fields' => [],
                'raw' => ['driver' => 'google-cloud-vision', 'side' => 'back'],
            ],
        ], $lookupResponse);
    }

    private function fakeIdentityServices(): void
    {
        $this->bindSequentialOcr([
            [
                'success' => true,
                'confidence' => 0.93,
                'text' => $this->frontArabicOcrText(),
                'fields' => [],
                'raw' => ['driver' => 'google-cloud-vision', 'side' => 'front'],
            ],
            [
                'success' => true,
                'confidence' => 0.89,
                'text' => $this->backArabicOcrText(),
                'fields' => [],
                'raw' => ['driver' => 'google-cloud-vision', 'side' => 'back'],
            ],
        ]);

        $this->app->bind(IdentityImageInspectionService::class, function () {
            return new class extends IdentityImageInspectionService {
                public function quality(UploadedFile $file): array
                {
                    return ['passed' => true, 'warnings' => []];
                }

                public function exif(UploadedFile $file): array
                {
                    return [];
                }

                public function validateLebaneseIdFields(array $fields): array
                {
                    return parent::validateLebaneseIdFields($fields);
                }
            };
        });
    }

    private function bindSequentialOcr(array $responses, ?array $lookupResponse = null): void
    {
        $this->app->bind(IdentityOcrService::class, function () use ($responses, $lookupResponse) {
            return new class($responses, $lookupResponse) extends IdentityOcrService {
                private int $index = 0;

                public function __construct(private array $responses, private ?array $lookupResponse)
                {
                }

                public function analyze(string $diskPath): array
                {
                    $response = $this->responses[$this->index] ?? end($this->responses);
                    $this->index++;

                    return $response;
                }

                public function analyzeForNationalIdLookup(string $diskPath): array
                {
                    if ($this->lookupResponse !== null) {
                        return $this->lookupResponse;
                    }

                    return $this->analyze($diskPath);
                }
            };
        });
    }

    private function frontArabicOcrText(): string
    {
        return "وزارة الداخلية\nالاسم: ثريا\nالشهرة: محمد\nاسم الأب: جورج\nاسم الأم وشهرتها: ماري خوري\nمحل الولادة: بيروت\nتاريخ الولادة: 1/2/1999\n123456789/\nبطاقة هوية";
    }

    private function backArabicOcrText(): string
    {
        return "تاريخ الإصدار: 5/4/2024\nفئة الدم: AB+\nالجنس: أنثى\nالوضع العائلي: عزباء\nرقم السجل: 456\nالمحلة أو القرية: الأشرفية\nالمحافظة: بيروت\nالقضاء: بيروت";
    }

    private function fakePng(string $name): File
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
