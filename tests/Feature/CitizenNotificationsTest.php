<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Models\User;
use App\Notifications\CitizenRequestUpdatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_update_notification_is_sent_via_database_and_mail_channels(): void
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
                'notes' => 'Approved by office.',
                'official_response_document_type' => 'Approval Letter',
                'official_response' => UploadedFile::fake()->create('approval.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        Notification::assertSentTo(
            $citizen,
            CitizenRequestUpdatedNotification::class,
            function (CitizenRequestUpdatedNotification $notification, array $channels) {
                return in_array('database', $channels, true)
                    && in_array('mail', $channels, true);
            }
        );
    }

    public function test_citizen_can_view_notifications_and_mark_them_as_read(): void
    {
        Mail::fake();
        Storage::fake('public');

        $office = $this->office();
        $citizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, $citizen);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'notes' => 'Completed by office.',
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        $citizen->refresh();
        $notification = $citizen->unreadNotifications()->first();

        $this->actingAs($citizen)
            ->get(route('citizen.notifications.index'))
            ->assertOk()
            ->assertSee('Request status updated');

        $this->actingAs($citizen)
            ->get(route('citizen.notifications.unread-count'))
            ->assertOk()
            ->assertJson([
                'unread_count' => 1,
            ]);

        $this->actingAs($citizen)
            ->patch(route('citizen.notifications.read', $notification))
            ->assertRedirect();

        $this->actingAs($citizen)
            ->get(route('citizen.notifications.unread-count'))
            ->assertOk()
            ->assertJson([
                'unread_count' => 0,
            ]);
    }

    public function test_appointment_update_creates_citizen_notification(): void
    {
        Mail::fake();

        $office = $this->office();
        $citizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, $citizen);
        $timeSlot = TimeSlot::create([
            'government_office_id' => $office->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'created_by' => $municipalityUser->id,
            'is_available' => false,
        ]);
        $appointment = Appointment::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $citizen->id,
            'government_office_id' => $office->id,
            'time_slot_id' => $timeSlot->id,
            'status' => Appointment::STATUS_REQUESTED,
        ]);

        $this->actingAs($municipalityUser)
            ->patch(route('municipality.appointments.update', $appointment), [
                'action' => 'approve',
                'municipality_notes' => 'See you soon.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $citizen->fresh()->unreadNotifications()->count());
        $this->assertSame('citizen_appointment_updated', $citizen->fresh()->unreadNotifications()->first()->data['kind']);
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
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
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

    private function serviceRequestForOffice(GovernmentOffice $office, User $citizen): ServiceRequest
    {
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

        return ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'message' => 'Citizen note',
        ]);
    }
}
