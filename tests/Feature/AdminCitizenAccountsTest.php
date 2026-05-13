<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCitizenAccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $citizenRole;

    private Role $municipalityRole;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['role' => 'admin']);
        $this->municipalityRole = Role::create(['role' => 'municipality']);
        $this->citizenRole = Role::create(['role' => 'citizen']);

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);
    }

    public function test_admin_can_view_citizens_list(): void
    {
        $citizen = $this->createCitizen('Citizen One', 'citizen@example.com', '70123456', 'active');

        $response = $this->actingAs($this->admin)->get(route('admin.citizens.index'));

        $response->assertOk();
        $response->assertSee('Manage Citizens');
        $response->assertSee((string) $citizen->id);
        $response->assertSee('Citizen One');
        $response->assertSee('citizen@example.com');
        $response->assertSee('70123456');
        $response->assertSeeHtml('<span class="badge badge-success">Active</span>');
        $response->assertSee('View');
        $response->assertSee('Deactivate');
        $response->assertSee('Manage Citizens');
    }

    public function test_admin_can_search_citizens(): void
    {
        $this->createCitizen('Matching Citizen', 'match@example.com', '70123456');
        $this->createCitizen('Other Citizen', 'other@example.com', '71123456');

        $response = $this->actingAs($this->admin)->get(route('admin.citizens.index', [
            'search' => '70123456',
        ]));

        $response->assertOk();
        $response->assertSeeHtml('name="search" value="70123456"');
        $response->assertSee('Matching Citizen');
        $response->assertDontSee('Other Citizen');
        $response->assertSee('Clear');
    }

    public function test_admin_can_view_citizen_details(): void
    {
        $citizen = $this->createCitizen('Detail Citizen', 'detail@example.com', '76123456');

        $response = $this->actingAs($this->admin)->get(route('admin.citizens.show', $citizen));

        $response->assertOk();
        $response->assertSee('Citizen Details');
        $response->assertSee('Detail Citizen');
        $response->assertSee('detail@example.com');
        $response->assertSee('76123456');
        $response->assertSee('Back to Citizens');
    }

    public function test_admin_can_activate_and_deactivate_a_citizen(): void
    {
        $citizen = $this->createCitizen('Toggle Citizen', 'toggle@example.com', '78123456', 'active');

        $this->actingAs($this->admin)
            ->patch(route('admin.citizens.deactivate', $citizen))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $citizen->refresh();
        $this->assertSame('inactive', $citizen->status);
        $this->assertFalse((bool) $citizen->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.citizens.activate', $citizen))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $citizen->refresh();
        $this->assertSame('active', $citizen->status);
        $this->assertTrue((bool) $citizen->is_active);
    }

    public function test_municipality_users_and_citizens_cannot_access_admin_citizen_pages(): void
    {
        $citizen = $this->createCitizen('Blocked Citizen', 'blocked@example.com', '79123456');
        $municipalityUser = User::create([
            'name' => 'Municipality User',
            'email' => 'municipality@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->municipalityRole->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);

        $protectedRequests = [
            ['GET', route('admin.citizens.index')],
            ['GET', route('admin.citizens.show', $citizen)],
            ['PATCH', route('admin.citizens.activate', $citizen)],
            ['PATCH', route('admin.citizens.deactivate', $citizen)],
        ];

        foreach ([$municipalityUser, $citizen] as $user) {
            foreach ($protectedRequests as [$method, $url]) {
                $this->actingAs($user)
                    ->call($method, $url)
                    ->assertForbidden();
            }
        }
    }

    private function createCitizen(string $name, string $email, string $phone, string $status = 'active'): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make('password123'),
            'role_id' => $this->citizenRole->id,
            'status' => $status,
            'is_active' => $status === 'active',
            'two_factor_enabled' => false,
        ]);
    }
}
