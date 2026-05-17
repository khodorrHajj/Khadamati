<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\RequestDocument;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\CitizenRequestSubmittedNotification;
use App\Notifications\NewServiceRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_create_request_for_active_service(): void
    {
        Notification::fake();

        $citizen = $this->userWithRole('citizen');
        $service = $this->service(['name' => 'Birth Certificate']);
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $service->government_office_id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Please process this request.',
            ]);

        $serviceRequest = ServiceRequest::first();

        $response->assertRedirect(route('citizen.requests.show', $serviceRequest));

        $this->assertDatabaseHas('service_requests', [
            'id' => $serviceRequest->id,
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'message' => 'Please process this request.',
        ]);

        $this->assertNotEmpty($serviceRequest->tracking_code);
        Notification::assertSentTo($municipalityUser, NewServiceRequestNotification::class);
        Notification::assertSentTo($citizen, CitizenRequestSubmittedNotification::class);
    }

    public function test_citizen_cannot_create_request_for_inactive_service(): void
    {
        $citizen = $this->userWithRole('citizen');
        $service = $this->service(['is_active' => false]);

        $this->actingAs($citizen)
            ->get(route('citizen.services.request.create', $service))
            ->assertNotFound();

        $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'This should not be created.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_citizen_can_upload_documents(): void
    {
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $service = $this->service();

        $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Attached documents.',
                'documents' => [
                    UploadedFile::fake()->create('id-card.pdf', 200, 'application/pdf'),
                    UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg'),
                ],
            ])
            ->assertRedirect();

        $documents = RequestDocument::all();

        $this->assertCount(2, $documents);
        $documents->each(function (RequestDocument $document) use ($citizen) {
            $this->assertSame($citizen->id, $document->uploaded_by);
            Storage::disk('public')->assertExists($document->document_path);
        });
    }

    public function test_documents_are_linked_to_the_request(): void
    {
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $service = $this->service();

        $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'documents' => [
                    UploadedFile::fake()->create('application.pdf', 120, 'application/pdf'),
                ],
            ]);

        $serviceRequest = ServiceRequest::with('requestDocuments')->first();

        $this->assertCount(1, $serviceRequest->requestDocuments);
        $this->assertSame($serviceRequest->id, $serviceRequest->requestDocuments->first()->service_request_id);
        $this->assertSame('application.pdf', $serviceRequest->requestDocuments->first()->original_name);
    }

    public function test_request_belongs_to_authenticated_citizen(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $service = $this->service();

        $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Owner check.',
            ]);

        $this->assertDatabaseHas('service_requests', [
            'user_id' => $citizen->id,
            'service_id' => $service->id,
        ]);

        $this->assertDatabaseMissing('service_requests', [
            'user_id' => $otherCitizen->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_citizen_can_download_request_receipt_pdf(): void
    {
        Notification::fake();

        $citizen = $this->userWithRole('citizen');
        $service = $this->service();

        $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Receipt check.',
            ])
            ->assertRedirect();

        $serviceRequest = ServiceRequest::firstOrFail();

        $this->actingAs($citizen)
            ->get(route('citizen.requests.receipt.download', $serviceRequest))
            ->assertOk()
            ->assertDownload($serviceRequest->tracking_code . '-receipt.pdf');
    }

    public function test_municipality_user_cannot_submit_citizen_request(): void
    {
        $municipalityUser = $this->userWithRole('municipality');
        $service = $this->service();

        $this->actingAs($municipalityUser)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Blocked.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_admin_cannot_submit_citizen_request(): void
    {
        $admin = $this->userWithRole('admin');
        $service = $this->service();

        $this->actingAs($admin)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Blocked.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('service_requests', 0);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['role' => $role]);

        return User::create(array_merge([
            'name' => ucfirst($role) . ' User',
            'email' => $role . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleModel->id,
            'is_active' => true,
        ], $attributes));
    }

    private function service(array $attributes = []): Service
    {
        $municipality = Municipality::create([
            'name' => 'Test Municipality ' . uniqid(),
        ]);

        $office = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Civil Registry Office ' . uniqid(),
            'service_type' => 'Civil Records',
            'status' => 'active',
        ]);

        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        return Service::create(array_merge([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'description' => 'Request an official certificate.',
            'price' => 10,
            'duration_days' => 2,
            'required_documents' => "ID card\nApplication form",
            'is_active' => true,
        ], $attributes));
    }
}
