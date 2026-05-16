<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\Role;
use App\Models\User;
use App\Services\IdentityOcrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        config()->set('identity.google_application_credentials', base_path('tests/Fixtures/google-vision-test-credentials.json'));
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

    public function test_citizen_can_upload_valid_id_and_google_ocr_extracts_name_fields(): void
    {
        $this->fakeGoogleVision();

        $response = $this->actingAs($this->citizen)->post(route('identity.verification.store'), [
            'id_image' => $this->fakePng('id-card.png'),
        ]);

        $response->assertRedirect(route('identity.verification.create'));
        $response->assertSessionHasNoErrors();

        $verification = IdentityVerification::where('user_id', $this->citizen->id)->firstOrFail();

        $this->assertSame(IdentityVerification::STATUS_NEEDS_REVIEW, $verification->status);
        $this->assertSame('Citizen', $verification->extracted_first_name);
        $this->assertSame('User', $verification->extracted_family_name);
        $this->assertSame('Citizen User', $verification->extracted_full_name);
        $this->assertNull($verification->extracted_id_number);
        $this->assertNull($verification->extracted_date_of_birth);
        $this->assertStringContainsString('Citizen User', $verification->ocr_raw_text);
        $this->assertSame('google-cloud-vision', $verification->ocr_raw_json['driver']);
        $this->assertFalse((bool) $this->citizen->fresh()->is_active);
        $this->assertStringStartsWith('identity-verifications/', $verification->id_image_path);
        Storage::disk('public')->assertExists($verification->id_image_path);
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
                'id_image' => $this->fakePng('id-card.png', 6000 * 1024),
            ])
            ->assertSessionHasErrors('id_image');
    }

    public function test_missing_ocr_fields_still_goes_to_admin_review_with_errors(): void
    {
        $this->fakeGoogleVision('Unreadable document');

        $this->actingAs($this->citizen)->post(route('identity.verification.store'), [
            'id_image' => $this->fakePng('id-card.png'),
        ])->assertRedirect(route('identity.verification.create'));

        $verification = IdentityVerification::where('user_id', $this->citizen->id)->firstOrFail();

        $this->assertSame(IdentityVerification::STATUS_NEEDS_REVIEW, $verification->status);
        $this->assertFalse($verification->validation_result_json['passed']);
        $this->assertContains('Name was not detected.', $verification->validation_result_json['errors']);
        $this->assertContains('Family name was not detected.', $verification->validation_result_json['errors']);
        $this->assertNotContains('ID number was not detected.', $verification->validation_result_json['errors']);
        $this->assertNotContains('Date of birth was not detected.', $verification->validation_result_json['warnings']);
    }

    public function test_arabic_ocr_text_adds_manual_review_warning_when_english_name_does_not_match(): void
    {
        $rawText = "الجمهورية اللبنانية\nبطاقة هوية\nالاسم : ثريا\nالشهرة : محمد";
        $this->fakeGoogleVision($rawText, [
            'first_name' => 'ثريا',
            'family_name' => 'محمد',
        ]);

        $this->actingAs($this->citizen)->post(route('identity.verification.store'), [
            'id_image' => $this->fakePng('id-card.png'),
        ])->assertRedirect(route('identity.verification.create'));

        $verification = IdentityVerification::where('user_id', $this->citizen->id)->firstOrFail();

        $this->assertSame(IdentityVerification::STATUS_NEEDS_REVIEW, $verification->status);
        $this->assertSame($rawText, $verification->ocr_raw_text);
        $this->assertSame('ثريا', $verification->extracted_first_name);
        $this->assertSame('محمد', $verification->extracted_family_name);
        $this->assertContains('Arabic OCR text detected. Manual admin review required.', $verification->validation_result_json['warnings']);
        $this->assertGreaterThan(0, $verification->ocr_confidence);
    }

    public function test_arabic_labels_are_parsed_from_raw_ocr_text(): void
    {
        $fields = app(IdentityOcrService::class)->extractFieldsFromText("الاسم : ثريا\nالشهرة : محمد");

        $this->assertSame('ثريا', $fields['first_name']);
        $this->assertSame('محمد', $fields['family_name']);
    }

    public function test_empty_ocr_raw_text_does_not_crash_and_goes_to_review(): void
    {
        $this->fakeGoogleVision('');

        $this->actingAs($this->citizen)->post(route('identity.verification.store'), [
            'id_image' => $this->fakePng('id-card.png'),
        ])->assertRedirect(route('identity.verification.create'));

        $verification = IdentityVerification::where('user_id', $this->citizen->id)->firstOrFail();

        $this->assertSame(IdentityVerification::STATUS_NEEDS_REVIEW, $verification->status);
        $this->assertSame('', $verification->ocr_raw_text);
        $this->assertContains('No text was detected. Try a clearer image or approve manually.', $verification->validation_result_json['warnings']);
    }

    public function test_admin_can_view_verification_review_page_and_uploaded_id_image(): void
    {
        $verification = $this->verificationForCitizen($this->citizen, [
            'ocr_raw_text' => "REPUBLIC\nCitizen User",
        ]);

        Storage::disk('public')->put($verification->id_image_path, 'fake-image');

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.show', $verification))
            ->assertOk()
            ->assertSee('Submitted ID Image')
            ->assertSee(route('admin.identity-verifications.image', $verification), false)
            ->assertSee('Raw OCR Text')
            ->assertSee('Citizen User');

        $this->actingAs($this->admin)
            ->get(route('admin.identity-verifications.image', $verification))
            ->assertOk();
    }

    public function test_non_admin_cannot_access_uploaded_id_image_route(): void
    {
        $verification = $this->verificationForCitizen($this->citizen);

        Storage::disk('public')->put($verification->id_image_path, 'fake-image');

        $this->actingAs($this->citizen)
            ->get(route('admin.identity-verifications.image', $verification))
            ->assertForbidden();
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

    public function test_admin_can_approve_verification_when_arabic_ocr_is_detected(): void
    {
        $verification = $this->verificationForCitizen($this->citizen, [
            'extracted_first_name' => 'ثريا',
            'extracted_family_name' => 'محمد',
            'extracted_full_name' => 'ثريا محمد',
            'ocr_raw_text' => "الاسم : ثريا\nالشهرة : محمد",
            'validation_result_json' => [
                'passed' => true,
                'errors' => [],
                'warnings' => ['Arabic OCR text detected. Manual admin review required.'],
            ],
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.identity-verifications.approve', $verification), [
                'admin_notes' => 'Arabic ID reviewed manually.',
            ])
            ->assertRedirect(route('admin.identity-verifications.show', $verification));

        $this->assertSame(IdentityVerification::STATUS_APPROVED, $verification->fresh()->status);
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
            'extracted_first_name' => 'Matching',
            'extracted_family_name' => 'Citizen',
            'extracted_full_name' => 'Matching Citizen',
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
            'extracted_first_name' => 'Other',
            'extracted_family_name' => 'Citizen',
            'extracted_full_name' => 'Other Citizen',
            'status' => IdentityVerification::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.identity-verifications.index', [
            'search' => 'Matching',
            'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
        ]));

        $response->assertOk();
        $response->assertSee((string) $matching->id);
        $response->assertSee('Matching');
        $response->assertDontSee('Other Citizen');
    }

    private function verificationForCitizen(User $user, array $overrides = []): IdentityVerification
    {
        return IdentityVerification::create(array_merge([
            'user_id' => $user->id,
            'status' => IdentityVerification::STATUS_NEEDS_REVIEW,
            'id_image_path' => 'identity-verifications/id-card.jpg',
            'extracted_first_name' => 'Citizen',
            'extracted_family_name' => 'User',
            'extracted_full_name' => 'Citizen User',
            'extracted_id_number' => null,
            'extracted_date_of_birth' => null,
            'ocr_confidence' => 0.91,
            'ocr_raw_text' => "LEBANESE REPUBLIC\nCitizen User",
            'ocr_raw_json' => ['driver' => 'google-cloud-vision'],
            'quality_result_json' => ['passed' => true, 'warnings' => []],
            'exif_result_json' => ['has_exif' => false, 'warnings' => ['No EXIF metadata found.']],
            'validation_result_json' => ['passed' => true, 'errors' => [], 'warnings' => []],
        ], $overrides));
    }

    private function fakeGoogleVision(string $text = "LEBANESE REPUBLIC\nIDENTITY CARD\nName: Citizen User\nID No: 123456789\nDOB: 1995-05-10", array $fields = []): void
    {
        $this->app->bind(IdentityOcrService::class, function () use ($text, $fields) {
            return new class($text, $fields) extends IdentityOcrService {
                public function __construct(private string $text, private array $fields)
                {
                }

                public function analyze(string $diskPath): array
                {
                    return [
                        'success' => filled($this->text),
                        'confidence' => 0.91,
                        'text' => $this->text,
                        'fields' => $this->fields,
                        'raw' => [
                            'driver' => 'google-cloud-vision',
                            'diagnostics' => [
                                'final_raw_text_length' => strlen($this->text),
                                'text_annotations_count' => filled($this->text) ? 1 : 0,
                            ],
                            'response' => [
                                'responses' => [[
                                    'fullTextAnnotation' => [
                                        'text' => $this->text,
                                    ],
                                ]],
                            ],
                        ],
                    ];
                }
            };
        });
    }

    private function fakePng(string $name, ?int $size = null): \Illuminate\Http\Testing\File
    {
        $content = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');

        if ($size !== null && $size > strlen($content)) {
            $content = str_pad($content, $size, '0');
        }

        return UploadedFile::fake()->createWithContent($name, $content);
    }
}
