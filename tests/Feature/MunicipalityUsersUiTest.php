<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunicipalityUsersUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $municipalityRole;

    private GovernmentOffice $office;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['role' => 'admin']);
        $this->municipalityRole = Role::create(['role' => 'municipality']);
        Role::create(['role' => 'citizen']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);

        $municipality = Municipality::create([
            'name' => 'Beirut Municipality',
            'status' => 'active',
        ]);

        $this->office = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Central Records Office',
            'status' => 'active',
        ]);
    }

    public function test_municipality_users_page_loads_with_empty_form_and_clear_office_names(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.municipality.users'));

        $response->assertOk();
        $response->assertSee('Manage Municipality Users');
        $response->assertSee('Beirut Municipality - Central Records Office');
        $response->assertSeeHtml('name="email" value=""');
        $response->assertSeeHtml('name="password" class="form-control');
        $response->assertSeeHtml('name="password_confirmation" class="form-control');

        $content = $response->getContent();

        $this->assertStringNotContainsString('testadmin@example.com', $content);
        $this->assertStringNotContainsString('type="password" name="password" value=', $content);
        $this->assertStringNotContainsString('name="password_confirmation" value=', $content);
    }

    public function test_validation_errors_show_old_values_except_passwords(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.municipality.users'))
            ->post(route('admin.municipality.users.store'), [
                'government_office_id' => $this->office->id,
                'name' => '',
                'email' => 'staff@example.com',
                'phone' => '70123456',
                'job_title' => 'Records Clerk',
                'password' => 'strongpass123',
                'password_confirmation' => 'different123',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.municipality.users'));

        $page = $this->actingAs($this->admin)
            ->withSession(session()->all())
            ->get(route('admin.municipality.users'));

        $page->assertSee('The name field is required.');
        $page->assertSee('The password field confirmation does not match.');
        $page->assertSeeHtml('name="email" value="staff@example.com"');
        $page->assertSeeHtml('name="phone" value="70123456"');
        $page->assertSeeHtml('name="job_title" value="Records Clerk"');

        $content = $page->getContent();

        $this->assertStringNotContainsString('value="strongpass123"', $content);
        $this->assertStringNotContainsString('value="different123"', $content);
    }

    public function test_table_displays_users_status_badges_actions_and_sidebar_active_state(): void
    {
        $this->createMunicipalityUser('Active Municipality User', 'active@example.com', 'active', 'Records Clerk');
        $this->createMunicipalityUser('Inactive Municipality User', 'inactive@example.com', 'inactive', 'Case Officer');

        $response = $this->actingAs($this->admin)->get(route('admin.municipality.users'));

        $response->assertOk();
        $response->assertSee('Active Municipality User');
        $response->assertSee('Inactive Municipality User');
        $response->assertSee('active@example.com');
        $response->assertSee('70123456');
        $response->assertSee('Central Records Office');
        $response->assertSee('Beirut Municipality');
        $response->assertSee('Records Clerk');
        $response->assertSeeHtml('<span class="badge badge-success">Active</span>');
        $response->assertSeeHtml('<span class="badge badge-secondary">Inactive</span>');
        $response->assertSee('Deactivate');
        $response->assertSee('Activate');
        $response->assertSee('data-widget="pushmenu"', false);
        $response->assertSee('href="' . route('admin.dashboard') . '"', false);
        $response->assertSee('Manage Municipality Users');
        $response->assertSee('nav-link active', false);
        $response->assertSee('table-responsive');
    }

    public function test_empty_state_is_displayed_when_no_users_match_search(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.municipality.users', [
            'search' => 'does-not-exist',
        ]));

        $response->assertOk();
        $response->assertSeeHtml('name="search" value="does-not-exist"');
        $response->assertSee('No municipality users found.');
        $response->assertSee('Clear');
    }

    private function createMunicipalityUser(string $name, string $email, string $status, string $jobTitle): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'phone' => '70123456',
            'password' => Hash::make('password123'),
            'role_id' => $this->municipalityRole->id,
            'government_office_id' => $this->office->id,
            'job_title' => $jobTitle,
            'status' => $status,
            'is_active' => $status === 'active',
            'two_factor_enabled' => false,
        ]);
    }
}
