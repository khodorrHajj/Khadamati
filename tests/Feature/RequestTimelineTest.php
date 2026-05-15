<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\RequestDocument;
use App\Models\RequestTimelineEntry;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_request_timeline_shows_submission_and_document_upload_events(): void
    {
        Notification::fake();
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $service = $this->service();

        $this->actingAs($citizen)
            ->post(route('citizen.services.request.store', $service), [
                'notes' => 'Please process this request.',
                'documents' => [
                    UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect();

        $serviceRequest = ServiceRequest::with('timelineEntries')->firstOrFail();

        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'request_submitted',
            'title' => 'Request submitted',
        ]);
        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'document_uploaded',
            'title' => 'Document uploaded',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('Request History')
            ->assertSee('Request submitted')
            ->assertSee('Document uploaded')
            ->assertSee('passport.pdf');
    }

    public function test_municipality_updates_append_status_and_official_response_timeline_entries(): void
    {
        Notification::fake();
        Storage::fake('public');

        $office = $this->office();
        $citizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, $citizen);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_APPROVED,
                'notes' => 'Approved by the office.',
                'official_response_document_type' => 'Approval Letter',
                'official_response' => UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'status_changed',
            'title' => 'Request status updated',
        ]);
        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'official_response_uploaded',
            'title' => 'Official response uploaded',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('Request status updated')
            ->assertSee('Official response uploaded');
    }

    public function test_appointment_request_and_approval_are_logged_in_request_timeline(): void
    {
        Mail::fake();
        Notification::fake();

        $office = $this->office();
        $citizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, $citizen);
        $slot = TimeSlot::create([
            'government_office_id' => $office->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0),
            'ends_at' => now()->addDays(2)->setTime(11, 0),
            'created_by' => $municipalityUser->id,
            'is_available' => true,
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.requests.appointments.store', $serviceRequest), [
                'time_slot_id' => $slot->id,
                'notes' => 'I prefer mornings.',
            ])
            ->assertRedirect(route('citizen.requests.show', $serviceRequest));

        $appointment = Appointment::firstOrFail();

        $this->actingAs($municipalityUser)
            ->patch(route('municipality.appointments.update', $appointment), [
                'action' => 'approve',
                'municipality_notes' => 'See you there.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'appointment_requested',
            'title' => 'Appointment requested',
        ]);
        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'appointment_approved',
            'title' => 'Appointment approved',
        ]);

        $this->actingAs($municipalityUser)
            ->get(route('municipality.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('Appointment requested')
            ->assertSee('Appointment approved');
    }

    public function test_admin_internal_note_only_change_does_not_create_timeline_entry(): void
    {
        Notification::fake();

        $office = $this->office();
        $serviceRequest = $this->serviceRequestForOffice($office);
        $admin = $this->userWithRole('admin');

        $this->assertSame(1, RequestTimelineEntry::where('service_request_id', $serviceRequest->id)->count());

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => $serviceRequest->status,
                'admin_internal_note' => 'Internal follow-up only.',
                'official_response_document_type' => 'Official Response',
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $this->assertSame(1, RequestTimelineEntry::where('service_request_id', $serviceRequest->id)->count());
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
            'status' => 'active',
            'email_verified_at' => now(),
            'two_factor_enabled' => false,
        ], $attributes));
    }

    private function office(): GovernmentOffice
    {
        $municipality = Municipality::create([
            'name' => 'Municipality ' . uniqid(),
            'status' => 'active',
        ]);

        return GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Office ' . uniqid(),
            'status' => 'active',
        ]);
    }

    private function service(array $attributes = []): Service
    {
        $office = $this->office();
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
            'is_active' => true,
        ], $attributes));
    }

    private function serviceRequestForOffice(
        GovernmentOffice $office,
        ?User $citizen = null,
        array $attributes = []
    ): ServiceRequest {
        $citizen ??= $this->userWithRole('citizen');

        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        $service = Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'price' => 10,
            'duration_days' => 2,
            'is_active' => true,
        ]);

        return ServiceRequest::create(array_merge([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'message' => 'Citizen note',
        ], $attributes));
    }
}
