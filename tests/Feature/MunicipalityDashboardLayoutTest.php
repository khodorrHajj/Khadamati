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

class MunicipalityDashboardLayoutTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $municipalityRole;

    private Role $citizenRole;

    private GovernmentOffice $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['role' => 'admin']);
        $this->municipalityRole = Role::create(['role' => 'municipality']);
        $this->citizenRole = Role::create(['role' => 'citizen']);

        $municipality = Municipality::create([
            'name' => 'Beirut Municipality',
        ]);

        $this->office = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Central Records Office',
            'status' => 'active',
        ]);
    }

    public function test_municipality_user_can_access_dashboard(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $category = ServiceCategory::create([
            'government_office_id' => $this->office->id,
            'name' => 'Permits',
        ]);
        $service = Service::create([
            'government_office_id' => $this->office->id,
            'service_category_id' => $category->id,
            'name' => 'Building Permit',
            'price' => 10,
            'duration_days' => 3,
            'is_active' => true,
        ]);
        $citizen = $this->createUser('citizen@example.com', $this->citizenRole);

        ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($user)->get(route('municipality.dashboard'));

        $response->assertOk();
        $response->assertSee('Municipality Dashboard');
        $response->assertSee('Central Records Office');
        $response->assertSee('Total Categories');
        $response->assertSee('Total Services');
        $response->assertSee('Total Requests');
        $response->assertSee('Pending Requests');
    }

    public function test_municipality_user_sees_the_sidebar(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);

        $response = $this->actingAs($user)->get(route('municipality.dashboard'));

        $response->assertOk();
        $response->assertSee('Municipality Portal');
        $response->assertSee('Dashboard');
        $response->assertSee('Office Profile');
        $response->assertSee('Categories');
        $response->assertSee('Services');
        $response->assertSee('Requests');
        $response->assertSee('Feedback');
        $response->assertSee('Messages');
        $response->assertSee('Appointments');
        $response->assertSee('Reports');
        $response->assertSee('nav-link active', false);
        $response->assertSee('Municipality User');
    }

    public function test_categories_and_services_pages_use_municipality_layout(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $category = ServiceCategory::create([
            'government_office_id' => $this->office->id,
            'name' => 'Permits',
        ]);

        Service::create([
            'government_office_id' => $this->office->id,
            'service_category_id' => $category->id,
            'name' => 'Building Permit',
            'price' => 10,
            'duration_days' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('municipality.categories'))
            ->assertOk()
            ->assertSee('Municipality Portal')
            ->assertSee('Manage Service Categories')
            ->assertSee('Permits');

        $this->actingAs($user)
            ->get(route('municipality.services'))
            ->assertOk()
            ->assertSee('Municipality Portal')
            ->assertSee('Manage Services')
            ->assertSee('Building Permit');
    }

    public function test_municipality_user_without_office_sees_no_office_page(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole);

        $response = $this->actingAs($user)->get(route('municipality.dashboard'));

        $response->assertOk();
        $response->assertSee('No Government Office Assigned');
        $response->assertSee('Please contact the admin.');
        $response->assertSee('Municipality Portal');
    }

    public function test_citizen_cannot_access_municipality_dashboard(): void
    {
        $citizen = $this->createUser('citizen@example.com', $this->citizenRole);

        $this->actingAs($citizen)
            ->get(route('municipality.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_is_not_redirected_incorrectly_from_municipality_dashboard(): void
    {
        $admin = $this->createUser('admin@example.com', $this->adminRole);

        $this->actingAs($admin)
            ->get(route('municipality.dashboard'))
            ->assertForbidden();
    }

    private function createUser(string $email, Role $role, ?GovernmentOffice $office = null): User
    {
        return User::create([
            'name' => $role->role === 'municipality' ? 'Municipality User' : 'Test User',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'government_office_id' => $office?->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);
    }
}
