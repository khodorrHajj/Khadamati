<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $citizen;

    private Role $citizenRole;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.google_vision_api_key', 'test-google-key');
        Storage::fake('public');

        $adminRole = Role::create(['role' => 'admin']);
        $this->citizenRole = Role::create(['role' => 'citizen']);
        Role::create(['role' => 'municipality']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);

        $this->citizen = User::create([
            'name' => 'Citizen User',
            'email' => 'citizen@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->citizenRole->id,
            'status' => 'inactive',
            'is_active' => false,
            'two_factor_enabled' => false,
        ]);
    }

    public function test_citizen_can_upload_valid_id_and_google_ocr_extracts_fields(): void
    {
        $this->fakeGoogleVision();

        $response = $this->actingAs($this->citizen)->post(route('identity.verification.store'), [
            'id_image' => UploadedFile::fake()->image('id-card.jpg', 1200, 800)->size(300),
        ]);

        $response->assertRedirect(route('identity.verification.create'));
        $response->assertSessionHasNoErrors();

        $verification = IdentityVerification::where('user_id', $this->citizen->id)->firstOrFail();

        $this->assertSame(IdentityVerification::STATUS_NEEDS_REVIEW, $verification->status);
        $this->assertSame('Test Citizen', $verification->extracted_full_name);
        $this->assertSame('123456789', $verification->extracted_id_number);
        $this->assertSame('1995-05-10', $verification->extracted_date_of_birth->format('Y-m-d'));
        $this->assertSame('google', $verification->ocr_raw_json['driver']);
        $this->assertFalse((bool) $this->citizen->fresh()->is_active);
        Storage::disk('public')->assertExists($verification->id_image_path);
        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://vision.googleapis.com/v1/images:annotate?key=test-google-key')
                && $request['requests'][0]['features'][0]['type'] === 'DOCUMENT_TEXT_DETECTION';
        });
    }

    public function test_email_verified_signup_creates_inactive_citizen_and_redirects_to_id_upload(): void
    {
        $this->withSession([
            'pending_registration' => [
                'name' => 'New Citizen',
                'email' => 'new.citizen@example.com',
                'password' => Hash::make('password123'),
                'role_id' => $this->citizenRole->id,
                'code' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(10)->toDateTimeString(),
            ],
        ])->post(route('twofactor.verify'), [
            'code' => '123456',
        ])->assertRedirect(route('identity.verification.create'));

        $user = User::where('email', 'new.citizen@example.com')->firstOrFail();

        $this->assertFalse((bool) $user->is_active);
        $this->assertSame('inactive', $user->status);
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_pending_citizen_can_login_only_to_identity_upload(): void
    {
        $this->post(route('dologin'), [
            'email' => $this->citizen->email,
            'password' => 'password123',
        ])->assertRedirect(route('identity.verification.create'));

        $this->assertAuthenticatedAs($this->citizen);
    }

    public function test_id_upload_rejects_invalid_type_and_oversized_file(): void
    {
        $this->actingAs($this->citizen)
            ->post(route('identity.verification.store'), [
                'id_image' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('id_image');

        $this->actingAs($this->citizen)
            ->post(route('identity.verification.store'), [
                'id_image' => UploadedFile::fake()->image('id-card.jpg')->size(6000),
            ])
            ->assertSessionHasErrors('id_image');
    }

    public function test_missing_ocr_fields_still_goes_to_admin_review_with_errors(): void
    {
        $this->fakeGoogleVision('Unreadable document');

        $this->actingAs($this->citizen)->post(route('identity.verification.store'), [
            'id_image' => UploadedFile::fake()->image('id-card.jpg', 1200, 800)->size(300),
        ])->assertRedirect(route('identity.verification.create'));

        $verification = IdentityVerification::where('user_id', $this->citizen->id)->firstOrFail();

        $this->assertSame(IdentityVerification::STATUS_NEEDS_REVIEW, $verification->status);
        $this->assertFalse($verification->validation_result_json['passed']);
        $this->assertContains('Full name was not detected.', $verification->validation_result_json['errors']);
        $this->assertContains('ID number was not detected.', $verification->validation_result_json['errors']);
    }

    public function test_inactive_citizen_cannot_access_citizen_dashboard_before_approval(): void
    {
        $this->actingAs($this->citizen)
            ->get(route('citizen.dashboard'))
            ->assertRedirect(route('identity.verification.create'))
            ->assertSessionHasErrors('identity');
    }

    public function test_admin_can_approve_verification_and_activate_citizen(): void
    {
        $verification = $this->verificationForCitizen($this->citizen);

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.approve', $verification), [
                'admin_notes' => 'Looks valid.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $verification));

        $verification->refresh();
        $this->citizen->refresh();

        $this->assertSame(IdentityVerification::STATUS_APPROVED, $verification->status);
        $this->assertSame($this->admin->id, $verification->reviewed_by);
        $this->assertTrue((bool) $this->citizen->is_active);
        $this->assertSame('active', $this->citizen->status);
    }

    public function test_admin_can_reject_verification_and_keep_citizen_inactive(): void
    {
        $verification = $this->verificationForCitizen($this->citizen);

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.reject', $verification), [
                'admin_notes' => 'Image is unreadable.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $verification));

        $verification->refresh();
        $this->citizen->refresh();

        $this->assertSame(IdentityVerification::STATUS_REJECTED, $verification->status);
        $this->assertSame('Image is unreadable.', $verification->admin_notes);
        $this->assertFalse((bool) $this->citizen->is_active);
        $this->assertSame('inactive', $this->citizen->status);
    }

    public function test_admin_cannot_approve_non_citizen_verification(): void
    {
        $verification = IdentityVerification::create([
            'user_id' => $this->admin->id,
            'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.approve', $verification))
            ->assertNotFound();
    }

    public function test_admin_queue_filters_by_status_and_search(): void
    {
        $matching = $this->verificationForCitizen($this->citizen, [
            'extracted_full_name' => 'Matching Citizen',
            'extracted_id_number' => 'ABC12345',
            'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
        ]);

        $otherCitizen = User::create([
            'name' => 'Other Citizen',
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->citizenRole->id,
            'status' => 'inactive',
            'is_active' => false,
            'two_factor_enabled' => false,
        ]);

        $this->verificationForCitizen($otherCitizen, [
            'extracted_full_name' => 'Other Citizen',
            'extracted_id_number' => 'ZZZ99999',
            'status' => IdentityVerification::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.identity-verifications.index', [
            'search' => 'ABC12345',
            'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
        ]));

        $response->assertOk();
        $response->assertSee((string) $matching->id);
        $response->assertSee('Matching Citizen');
        $response->assertDontSee('ZZZ99999');
    }

    private function verificationForCitizen(User $user, array $overrides = []): IdentityVerification
    {
        return IdentityVerification::create(array_merge([
            'user_id' => $user->id,
            'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
            'id_image_path' => 'identity-verifications/id-card.jpg',
            'extracted_full_name' => 'Test Citizen',
            'extracted_id_number' => '123456789',
            'extracted_date_of_birth' => '1995-05-10',
            'ocr_confidence' => 0.91,
            'ocr_raw_json' => ['driver' => 'google'],
            'quality_result_json' => ['passed' => true, 'warnings' => []],
            'exif_result_json' => ['has_exif' => false, 'warnings' => ['No EXIF metadata found.']],
            'validation_result_json' => ['passed' => true, 'errors' => [], 'warnings' => []],
        ], $overrides));
    }

    private function fakeGoogleVision(string $text = "LEBANESE REPUBLIC\nIDENTITY CARD\nName: Test Citizen\nID No: 123456789\nDOB: 1995-05-10"): void
    {
        Http::fake([
            'vision.googleapis.com/*' => Http::response($this->googleVisionResponse($text), 200),
        ]);
    }

    private function googleVisionResponse(string $text = "LEBANESE REPUBLIC\nIDENTITY CARD\nName: Test Citizen\nID No: 123456789\nDOB: 1995-05-10"): array
    {
        return [
            'responses' => [[
                'fullTextAnnotation' => [
                    'text' => $text,
                    'pages' => [[
                        'blocks' => [
                            ['confidence' => 0.91],
                            ['confidence' => 0.89],
                        ],
                    ]],
                ],
            ]],
        ];
    }
}
