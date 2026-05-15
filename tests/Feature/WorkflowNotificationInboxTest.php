<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WorkflowNotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_mark_escalation_notifications_as_read(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_IN_REVIEW,
                'notes' => 'We need admin approval to continue.',
                'escalate_to_admin' => '1',
                'escalation_reason' => 'Escalating because the submitted files need policy review.',
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        $notification = $admin->fresh()->unreadNotifications()->first();

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Request escalated to admin')
            ->assertSee('Escalating because the submitted files need policy review.');

        $this->actingAs($admin)
            ->patch(route('admin.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_municipality_user_can_view_and_mark_assignment_notifications_as_read(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, [
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
            'escalation_reason' => 'Waiting for admin review.',
            'escalated_to_admin_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => $serviceRequest->status,
                'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY,
                'assigned_to_user_id' => $municipalityUser->id,
                'official_response_document_type' => 'Official Response',
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $notification = $municipalityUser->fresh()->unreadNotifications()->first();

        $this->actingAs($municipalityUser)
            ->get(route('municipality.notifications.index'))
            ->assertOk()
            ->assertSee('Request assigned to you')
            ->assertSee($serviceRequest->tracking_code);

        $this->actingAs($municipalityUser)
            ->patch(route('municipality.notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['role' => $role]);

        return User::create(array_merge([
            'name' => ucfirst($role) . ' User ' . uniqid(),
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

    private function serviceRequestForOffice(GovernmentOffice $office, array $attributes = []): ServiceRequest
    {
        $citizen = $this->userWithRole('citizen');

        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates ' . uniqid(),
        ]);

        $service = Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate ' . uniqid(),
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
