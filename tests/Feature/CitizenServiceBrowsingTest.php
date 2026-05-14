<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\GovernmentOfficeWorkingHour;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CitizenServiceBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_browse_active_services(): void
    {
        $citizen = $this->userWithRole('citizen');
        $service = $this->service(['name' => 'Birth Certificate']);

        $this->actingAs($citizen)
            ->get(route('citizen.services.index'))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertSee($service->serviceCategory->name)
            ->assertSee($service->governmentOffice->name)
            ->assertSee($service->governmentOffice->municipality->name)
            ->assertSee('Start Request');
    }

    public function test_inactive_services_are_hidden(): void
    {
        $citizen = $this->userWithRole('citizen');
        $office = $this->office();
        $category = $this->category($office);

        $this->service(['name' => 'Active Permit'], $office, $category);
        $this->service(['name' => 'Inactive Permit', 'is_active' => false], $office, $category);

        $this->actingAs($citizen)
            ->get(route('citizen.services.index'))
            ->assertOk()
            ->assertSee('Active Permit')
            ->assertDontSee('Inactive Permit');
    }

    public function test_citizen_can_filter_services_by_category(): void
    {
        $citizen = $this->userWithRole('citizen');
        $office = $this->office();
        $certificates = $this->category($office, ['name' => 'Certificates']);
        $permits = $this->category($office, ['name' => 'Permits']);

        $this->service(['name' => 'Birth Certificate'], $office, $certificates);
        $this->service(['name' => 'Parking Permit'], $office, $permits);

        $this->actingAs($citizen)
            ->get(route('citizen.services.index', ['category' => $certificates->id]))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertDontSee('Parking Permit');
    }

    public function test_citizen_can_filter_services_by_office(): void
    {
        $citizen = $this->userWithRole('citizen');
        $registryOffice = $this->office(['name' => 'Civil Registry Office']);
        $taxOffice = $this->office(['name' => 'Tax Office']);

        $this->service(['name' => 'Birth Certificate'], $registryOffice, $this->category($registryOffice));
        $this->service(['name' => 'Tax Clearance'], $taxOffice, $this->category($taxOffice));

        $this->actingAs($citizen)
            ->get(route('citizen.services.index', ['office' => $registryOffice->id]))
            ->assertOk()
            ->assertSee('Birth Certificate')
            ->assertDontSee('Tax Clearance');
    }

    public function test_citizen_can_view_office_details(): void
    {
        $citizen = $this->userWithRole('citizen');
        $office = $this->office([
            'name' => 'Civil Registry Office',
            'service_type' => 'Civil Records',
            'phone' => '01-555-010',
            'email' => 'registry@example.com',
            'street' => 'Main Street',
            'city' => 'Beirut',
            'working_hours' => 'Monday to Friday, 8:00 AM - 2:00 PM',
            'google_maps_url' => 'https://maps.example.test/registry',
        ]);
        $category = $this->category($office, ['name' => 'Certificates']);
        $this->service(['name' => 'Birth Certificate'], $office, $category);

        GovernmentOfficeWorkingHour::create([
            'government_office_id' => $office->id,
            'day_of_week' => 'Monday',
            'is_open' => true,
            'start_time' => '08:00',
            'end_time' => '14:00',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.offices.show', $office))
            ->assertOk()
            ->assertSee('Civil Registry Office')
            ->assertSee($office->municipality->name)
            ->assertSee('Civil Records')
            ->assertSee('Main Street')
            ->assertSee('01-555-010')
            ->assertSee('registry@example.com')
            ->assertSee('Monday')
            ->assertSee('Open map')
            ->assertSee('Birth Certificate');
    }

    public function test_admin_and_municipality_users_cannot_access_citizen_browsing_pages(): void
    {
        $admin = $this->userWithRole('admin');
        $municipalityUser = $this->userWithRole('municipality');

        $this->actingAs($admin)
            ->get(route('citizen.services.index'))
            ->assertForbidden();

        $this->actingAs($municipalityUser)
            ->get(route('citizen.offices.index'))
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

    private function office(array $attributes = []): GovernmentOffice
    {
        $municipality = Municipality::create([
            'name' => $attributes['municipality_name'] ?? 'Test Municipality ' . uniqid(),
        ]);
        unset($attributes['municipality_name']);

        return GovernmentOffice::create(array_merge([
            'municipality_id' => $municipality->id,
            'name' => 'Government Office ' . uniqid(),
            'service_type' => 'Public Services',
            'status' => 'active',
        ], $attributes));
    }

    private function category(GovernmentOffice $office, array $attributes = []): ServiceCategory
    {
        return ServiceCategory::create(array_merge([
            'government_office_id' => $office->id,
            'name' => 'Category ' . uniqid(),
        ], $attributes));
    }

    private function service(
        array $attributes = [],
        ?GovernmentOffice $office = null,
        ?ServiceCategory $category = null
    ): Service {
        $office ??= $this->office();
        $category ??= $this->category($office);

        return Service::create(array_merge([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Service ' . uniqid(),
            'description' => 'Service description',
            'price' => 10,
            'duration_days' => 2,
            'required_documents' => "ID card\nApplication form",
            'is_active' => true,
        ], $attributes));
    }
}
