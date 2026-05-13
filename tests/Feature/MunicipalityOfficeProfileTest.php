<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\GovernmentOfficeWorkingHour;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunicipalityOfficeProfileTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;

    private Role $municipalityRole;

    private Role $citizenRole;

    private Municipality $municipality;

    private GovernmentOffice $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['role' => 'admin']);
        $this->municipalityRole = Role::create(['role' => 'municipality']);
        $this->citizenRole = Role::create(['role' => 'citizen']);

        $this->municipality = Municipality::create([
            'name' => 'Beirut Municipality',
            'status' => 'active',
        ]);

        $this->office = GovernmentOffice::create([
            'municipality_id' => $this->municipality->id,
            'name' => 'Central Records Office',
            'service_type' => 'Civil Registry',
            'phone' => '01123456',
            'email' => 'records@example.com',
            'city' => 'Beirut',
            'street' => 'Main Street',
            'building' => 'Municipal Building',
            'google_maps_url' => 'https://maps.google.com/?q=beirut',
            'latitude' => 33.8938000,
            'longitude' => 35.5018000,
            'place_id' => 'place-123',
            'formatted_address' => 'Main Street, Beirut',
            'status' => 'active',
            'notes' => 'Bring official documents.',
        ]);

        GovernmentOfficeWorkingHour::create([
            'government_office_id' => $this->office->id,
            'day_of_week' => 'Monday',
            'is_open' => true,
            'start_time' => '08:00',
            'end_time' => '14:00',
        ]);
    }

    public function test_municipality_user_can_view_assigned_office_profile(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);

        $response = $this->actingAs($user)->get(route('municipality.office.show'));

        $response->assertOk();
        $response->assertSee('Office Profile');
        $response->assertSee('Central Records Office');
        $response->assertSee('Beirut Municipality');
        $response->assertSee('Civil Registry');
        $response->assertSee('records@example.com');
        $response->assertSee('33.8938');
        $response->assertSee('35.5018');
        $response->assertSee('Bring official documents.');
        $response->assertSee('Working Hours');
        $response->assertSee('Edit Office Profile');
    }

    public function test_municipality_user_can_update_assigned_office_profile(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);

        $response = $this->actingAs($user)->put(route('municipality.office.update'), $this->validPayload([
            'name' => 'Updated Records Office',
            'email' => 'updated@example.com',
            'working_hours' => $this->workingHoursPayload('09:00', '15:00'),
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('municipality.office.show'));

        $this->office->refresh();
        $this->assertSame('Updated Records Office', $this->office->name);
        $this->assertSame('updated@example.com', $this->office->email);
        $this->assertSame('General Services', $this->office->service_type);
        $this->assertSame('New notes', $this->office->notes);

        $this->assertDatabaseHas('government_office_working_hours', [
            'government_office_id' => $this->office->id,
            'day_of_week' => 'Monday',
            'is_open' => true,
            'start_time' => '09:00',
            'end_time' => '15:00',
        ]);
    }

    public function test_municipality_user_cannot_update_another_office(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $otherOffice = GovernmentOffice::create([
            'municipality_id' => $this->municipality->id,
            'name' => 'Other Office',
            'email' => 'other@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($user)->patch(route('municipality.office.update'), $this->validPayload([
            'name' => 'Assigned Office Updated',
            'email' => 'assigned@example.com',
            'government_office_id' => $otherOffice->id,
            'municipality_id' => $otherOffice->municipality_id,
        ]))->assertSessionHasNoErrors();

        $this->office->refresh();
        $otherOffice->refresh();

        $this->assertSame('Assigned Office Updated', $this->office->name);
        $this->assertSame('Other Office', $otherOffice->name);
        $this->assertSame('other@example.com', $otherOffice->email);
    }

    public function test_municipality_user_without_office_sees_no_office(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole);

        $this->actingAs($user)
            ->get(route('municipality.office.show'))
            ->assertOk()
            ->assertSee('No Government Office Assigned')
            ->assertSee('Please contact the admin.');

        $this->actingAs($user)
            ->get(route('municipality.office.edit'))
            ->assertOk()
            ->assertSee('No Government Office Assigned');
    }

    public function test_citizen_cannot_access_office_profile(): void
    {
        $citizen = $this->createUser('citizen@example.com', $this->citizenRole);

        $this->actingAs($citizen)
            ->get(route('municipality.office.show'))
            ->assertForbidden();
    }

    public function test_admin_cannot_accidentally_use_municipality_office_profile(): void
    {
        $admin = $this->createUser('admin@example.com', $this->adminRole);

        $this->actingAs($admin)
            ->get(route('municipality.office.show'))
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Updated Office',
            'service_type' => 'General Services',
            'phone' => '01987654',
            'email' => 'office@example.com',
            'city' => 'Beirut',
            'street' => 'Updated Street',
            'building' => 'Updated Building',
            'google_maps_url' => 'https://maps.google.com/?q=updated',
            'latitude' => '33.9000000',
            'longitude' => '35.5000000',
            'place_id' => 'updated-place',
            'formatted_address' => 'Updated Street, Beirut',
            'notes' => 'New notes',
            'working_hours' => $this->workingHoursPayload(),
        ], $overrides);
    }

    private function workingHoursPayload(string $start = '08:00', string $end = '14:00'): array
    {
        $hours = [];

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $isOpen = in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);

            $hours[$day] = [
                'is_open' => $isOpen ? '1' : '0',
                'start_time' => $isOpen ? $start : null,
                'end_time' => $isOpen ? $end : null,
            ];
        }

        return $hours;
    }
}
