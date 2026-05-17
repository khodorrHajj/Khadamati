<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\RequestMessage;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CitizenDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_access_dashboard(): void
    {
        $citizen = $this->userWithRole('citizen');

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('Citizen Dashboard');
    }

    public function test_citizen_sees_sidebar_links(): void
    {
        $citizen = $this->userWithRole('citizen');

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Browse Services')
            ->assertSee('My Requests')
            ->assertSee('Appointments')
            ->assertSee('Messages')
            ->assertSee('Payments')
            ->assertSee('Notifications')
            ->assertSee('Profile');
    }

    public function test_dashboard_counters_are_scoped_to_authenticated_citizen(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality');
        $service = $this->service();

        $pendingRequest = ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
        ]);

        $completedRequest = ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        $paymentRequest = ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_APPROVED,
        ]);

        ServiceRequest::create([
            'user_id' => $otherCitizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
        ]);

        $slot = TimeSlot::create([
            'government_office_id' => $service->government_office_id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'is_available' => true,
        ]);

        Appointment::create([
            'service_request_id' => $pendingRequest->id,
            'user_id' => $citizen->id,
            'government_office_id' => $service->government_office_id,
            'time_slot_id' => $slot->id,
            'status' => Appointment::STATUS_APPROVED,
        ]);

        RequestMessage::create([
            'service_request_id' => $completedRequest->id,
            'sender_id' => $municipalityUser->id,
            'body' => 'Please review the office response.',
        ]);

        RequestMessage::create([
            'service_request_id' => $paymentRequest->id,
            'sender_id' => $citizen->id,
            'body' => 'Citizen message should not count as unread incoming.',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                '3',
                'Total Requests',
                '1',
                'Pending Requests',
                '1',
                'Completed Requests',
                '1',
                'Upcoming Appointments',
                '1',
                'Unread Messages',
                '1',
                'Pending Payments',
            ])
            ->assertSee('#' . $pendingRequest->id)
            ->assertSee('#' . $completedRequest->id);
    }

    public function test_admin_cannot_access_citizen_dashboard(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('citizen.dashboard'))
            ->assertForbidden();
    }

    public function test_municipality_user_cannot_access_citizen_dashboard(): void
    {
        $municipalityUser = $this->userWithRole('municipality');

        $this->actingAs($municipalityUser)
            ->get(route('citizen.dashboard'))
            ->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        $roleModel = Role::firstOrCreate(['role' => $role]);

        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleModel->id,
            'is_active' => true,
        ]);
    }

    private function service(): Service
    {
        $municipality = Municipality::create([
            'name' => 'Test Municipality',
        ]);

        $office = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Civil Registry Office',
            'status' => 'active',
        ]);

        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        return Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'price' => 15,
            'duration_days' => 3,
            'is_active' => true,
        ]);
    }
}
