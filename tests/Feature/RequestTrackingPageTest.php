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

class RequestTrackingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tracking_page_contains_qr_widget_and_hides_citizen_identity(): void
    {
        $citizen = $this->userWithRole('citizen', [
            'name' => 'Hidden Citizen',
            'email' => 'hidden-citizen@example.com',
        ]);

        $serviceRequest = $this->serviceRequestFor($citizen, [
            'tracking_code' => 'REQ-TRACK-QR',
        ]);

        $this->get(route('tracking.show', $serviceRequest->tracking_code))
            ->assertOk()
            ->assertSee('Scan This Tracking QR')
            ->assertSee('data-request-tracking-qr', false)
            ->assertSee(route('tracking.show', $serviceRequest->tracking_code), false)
            ->assertDontSee('Hidden Citizen')
            ->assertDontSee('hidden-citizen@example.com');
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

    private function office(): GovernmentOffice
    {
        $municipality = Municipality::create([
            'name' => 'Municipality ' . uniqid(),
        ]);

        return GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Office ' . uniqid(),
            'status' => 'active',
        ]);
    }

    private function service(GovernmentOffice $office): Service
    {
        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        return Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'price' => 10,
            'duration_days' => 2,
            'is_active' => true,
        ]);
    }

    private function serviceRequestFor(User $citizen, array $attributes = []): ServiceRequest
    {
        $service = $this->service($this->office());

        return ServiceRequest::create(array_merge([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'message' => 'Request notes',
        ], $attributes));
    }
}
