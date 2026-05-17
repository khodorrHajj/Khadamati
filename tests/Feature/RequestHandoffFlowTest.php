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

class RequestHandoffFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipality_can_escalate_request_to_admin_review(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestForOffice($office, $citizen, [
            'status' => ServiceRequest::STATUS_IN_REVIEW,
        ]);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_IN_REVIEW,
                'notes' => 'We are checking the file.',
                'escalate_to_admin' => '1',
                'escalation_reason' => 'This request needs admin approval before the office can continue.',
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame(ServiceRequest::WORKFLOW_AWAITING_ADMIN, $serviceRequest->workflow_state);
        $this->assertSame('This request needs admin approval before the office can continue.', $serviceRequest->escalation_reason);
        $this->assertNotNull($serviceRequest->escalated_to_admin_at);
        $this->assertSame('request_escalated_to_admin', $admin->fresh()->unreadNotifications()->first()->data['kind']);
        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'escalated_to_admin',
        ]);
    }

    public function test_admin_can_assign_request_back_to_a_municipality_user(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $assignedMunicipalityUser = $this->userWithRole('municipality', [
            'name' => 'Assigned Officer',
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, null, [
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
            'escalation_reason' => 'Waiting for admin decision.',
            'escalated_to_admin_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => $serviceRequest->status,
                'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY,
                'assigned_to_user_id' => $assignedMunicipalityUser->id,
                'official_response_document_type' => 'Official Response',
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame(ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY, $serviceRequest->workflow_state);
        $this->assertSame($assignedMunicipalityUser->id, $serviceRequest->assigned_to_user_id);
        $this->assertSame($admin->id, $serviceRequest->assigned_by_user_id);
        $this->assertNotNull($serviceRequest->assigned_at);
        $this->assertNull($serviceRequest->escalated_to_admin_at);
        $this->assertNull($serviceRequest->escalation_reason);
        $this->assertSame('request_assigned_to_municipality', $assignedMunicipalityUser->fresh()->unreadNotifications()->first()->data['kind']);
        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'assigned_to_municipality_user',
        ]);
        $this->assertDatabaseHas('request_timeline_entries', [
            'service_request_id' => $serviceRequest->id,
            'event_type' => 'returned_to_municipality',
        ]);
    }

    public function test_escalating_request_clears_previous_assignment_context(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, null, [
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY,
            'assigned_to_user_id' => $municipalityUser->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now()->subHour(),
        ]);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_IN_REVIEW,
                'notes' => 'Escalating this request for policy review.',
                'escalate_to_admin' => '1',
                'escalation_reason' => 'Needs admin confirmation before we continue.',
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame(ServiceRequest::WORKFLOW_AWAITING_ADMIN, $serviceRequest->workflow_state);
        $this->assertNull($serviceRequest->assigned_to_user_id);
        $this->assertNull($serviceRequest->assigned_by_user_id);
        $this->assertNull($serviceRequest->assigned_at);
    }

    public function test_closing_request_clears_handoff_context(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, null, [
            'status' => ServiceRequest::STATUS_IN_REVIEW,
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
            'assigned_to_user_id' => $municipalityUser->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now()->subDay(),
            'escalated_to_admin_at' => now()->subHours(2),
            'escalation_reason' => 'Escalation still open.',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'admin_internal_note' => 'Resolved after final admin check.',
                'official_response_document_type' => 'Official Response',
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame(ServiceRequest::STATUS_COMPLETED, $serviceRequest->status);
        $this->assertSame(ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY, $serviceRequest->workflow_state);
        $this->assertNull($serviceRequest->assigned_to_user_id);
        $this->assertNull($serviceRequest->assigned_by_user_id);
        $this->assertNull($serviceRequest->assigned_at);
        $this->assertNull($serviceRequest->escalated_to_admin_at);
        $this->assertNull($serviceRequest->escalation_reason);
    }

    public function test_admin_and_municipality_filters_support_handoff_states(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $assignedMunicipalityUser = $this->userWithRole('municipality', [
            'name' => 'Assigned Officer',
            'government_office_id' => $office->id,
        ]);

        $awaitingAdminRequest = $this->serviceRequestForOffice($office, null, [
            'tracking_code' => 'REQ-AWAITING-ADMIN',
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
            'escalation_reason' => 'Need admin review.',
            'escalated_to_admin_at' => now(),
        ]);
        $assignedRequest = $this->serviceRequestForOffice($office, null, [
            'tracking_code' => 'REQ-ASSIGNED-OFFICER',
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY,
            'assigned_to_user_id' => $assignedMunicipalityUser->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests.index', [
                'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
            ]))
            ->assertOk()
            ->assertSee('REQ-AWAITING-ADMIN')
            ->assertDontSee('REQ-ASSIGNED-OFFICER');

        $this->actingAs($admin)
            ->get(route('admin.requests.index', [
                'assigned_to_user_id' => $assignedMunicipalityUser->id,
            ]))
            ->assertOk()
            ->assertSee('REQ-ASSIGNED-OFFICER')
            ->assertDontSee('REQ-AWAITING-ADMIN');

        $this->actingAs($assignedMunicipalityUser)
            ->get(route('municipality.requests.index', [
                'handoff_scope' => 'assigned_to_me',
            ]))
            ->assertOk()
            ->assertSee('Assigned to Assigned Officer')
            ->assertDontSee('Need admin review.');

        $this->actingAs($municipalityUser)
            ->get(route('municipality.requests.index', [
                'handoff_scope' => 'awaiting_admin',
            ]))
            ->assertOk()
            ->assertSee('Awaiting Admin')
            ->assertDontSee('Assigned to Assigned Officer');
    }

    public function test_admin_and_municipality_dashboards_surface_workflow_counts(): void
    {
        $office = $this->office();
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);

        $awaitingAdminRequest = $this->serviceRequestForOffice($office, null, [
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_ADMIN,
            'escalation_reason' => 'Need admin review.',
            'escalated_to_admin_at' => now(),
        ]);

        $assignedToMeRequest = $this->serviceRequestForOffice($office, null, [
            'workflow_state' => ServiceRequest::WORKFLOW_AWAITING_MUNICIPALITY,
            'assigned_to_user_id' => $municipalityUser->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);
        $assignedToMeRequest->forceFill([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('requestStats', function (array $stats) {
                return $stats['awaitingAdmin'] === 1
                    && $stats['unassigned'] >= 1
                    && $stats['overdue'] >= 1;
            });

        $this->actingAs($municipalityUser)
            ->get(route('municipality.dashboard'))
            ->assertOk()
            ->assertViewHas('assignedToMeRequests', 1)
            ->assertViewHas('awaitingAdminRequests', 1)
            ->assertViewHas('overdueRequests', 1);
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

    private function serviceRequestForOffice(
        GovernmentOffice $office,
        ?User $citizen = null,
        array $attributes = []
    ): ServiceRequest {
        $citizen ??= $this->userWithRole('citizen');

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
