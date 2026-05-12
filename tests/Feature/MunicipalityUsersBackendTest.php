<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunicipalityUsersBackendTest extends TestCase
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
            'name' => 'Central Office',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_municipality_user_with_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.municipality.users.store'), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'Municipality Staff',
            'email' => 'staff@example.com',
            'phone' => '70123456',
            'role_id' => $this->municipalityRole->id,
            'government_office_id' => $this->office->id,
            'job_title' => 'Clerk',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_municipality_user_create_validation_rejects_invalid_data(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'duplicate@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->municipalityRole->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);

        $cases = [
            'missing name' => [
                array_merge($this->validPayload(), ['name' => '']),
                'name',
            ],
            'duplicate email' => [
                array_merge($this->validPayload(), ['email' => 'duplicate@example.com']),
                'email',
            ],
            'invalid email' => [
                array_merge($this->validPayload(), ['email' => 'not-an-email']),
                'email',
            ],
            'weak password' => [
                array_merge($this->validPayload(), [
                    'password' => 'short',
                    'password_confirmation' => 'short',
                ]),
                'password',
            ],
            'password confirmation mismatch' => [
                array_merge($this->validPayload(), ['password_confirmation' => 'different123']),
                'password',
            ],
            'missing government office' => [
                array_merge($this->validPayload(), ['government_office_id' => '']),
                'government_office_id',
            ],
            'phone with letters' => [
                array_merge($this->validPayload(), ['phone' => 'abc123']),
                'phone',
            ],
        ];

        foreach ($cases as $case) {
            [$payload, $field] = $case;

            $this->actingAs($this->admin)
                ->from(route('admin.municipality.users'))
                ->post(route('admin.municipality.users.store'), $payload)
                ->assertSessionHasErrors($field);
        }
    }

    public function test_password_is_hashed_and_role_office_status_are_saved(): void
    {
        $this->actingAs($this->admin)->post(route('admin.municipality.users.store'), $this->validPayload([
            'status' => 'inactive',
        ]));

        $user = User::where('email', 'staff@example.com')->firstOrFail();

        $this->assertNotSame('strongpass123', $user->password);
        $this->assertTrue(Hash::check('strongpass123', $user->password));
        $this->assertTrue($user->role->is($this->municipalityRole));
        $this->assertSame($this->office->id, $user->government_office_id);
        $this->assertSame('inactive', $user->status);
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_admin_can_toggle_only_municipality_user_status(): void
    {
        $municipalityUser = User::create([
            'name' => 'Municipality Staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->municipalityRole->id,
            'government_office_id' => $this->office->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.municipality.users.toggle-status', $municipalityUser))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $municipalityUser->refresh();
        $this->assertSame('inactive', $municipalityUser->status);
        $this->assertFalse((bool) $municipalityUser->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.municipality.users.toggle-status', $municipalityUser))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $municipalityUser->refresh();
        $this->assertSame('active', $municipalityUser->status);
        $this->assertTrue((bool) $municipalityUser->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.municipality.users.toggle-status', $this->admin))
            ->assertSessionHasErrors('user');
    }

    public function test_inactive_municipality_user_cannot_access_protected_municipality_pages(): void
    {
        $municipalityUser = User::create([
            'name' => 'Inactive Municipality Staff',
            'email' => 'inactive.staff@example.com',
            'password' => Hash::make('password123'),
            'role_id' => $this->municipalityRole->id,
            'government_office_id' => $this->office->id,
            'status' => 'inactive',
            'is_active' => false,
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($municipalityUser)
            ->get(route('municipality.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'government_office_id' => $this->office->id,
            'name' => 'Municipality Staff',
            'email' => 'staff@example.com',
            'phone' => '70123456',
            'password' => 'strongpass123',
            'password_confirmation' => 'strongpass123',
            'job_title' => 'Clerk',
            'status' => 'active',
        ], $overrides);
    }
}
