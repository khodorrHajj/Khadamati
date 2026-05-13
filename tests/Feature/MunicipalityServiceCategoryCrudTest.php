<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MunicipalityServiceCategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    private Role $municipalityRole;

    private Role $citizenRole;

    private GovernmentOffice $office;

    private GovernmentOffice $otherOffice;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['role' => 'admin']);
        $this->municipalityRole = Role::create(['role' => 'municipality']);
        $this->citizenRole = Role::create(['role' => 'citizen']);

        $municipality = Municipality::create([
            'name' => 'Beirut Municipality',
            'status' => 'active',
        ]);

        $this->office = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Central Records Office',
            'status' => 'active',
        ]);

        $this->otherOffice = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Other Office',
            'status' => 'active',
        ]);
    }

    public function test_municipality_user_can_create_a_category(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);

        $response = $this->actingAs($user)->post(route('municipality.categories.store'), [
            'name' => 'Permits',
            'description' => 'Permit services',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('municipality.categories'));

        $this->assertDatabaseHas('service_categories', [
            'government_office_id' => $this->office->id,
            'name' => 'Permits',
            'description' => 'Permit services',
        ]);
    }

    public function test_municipality_user_can_update_own_category(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $category = $this->createCategory($this->office, 'Old Name', 'Old description');

        $response = $this->actingAs($user)->put(route('municipality.categories.update', $category), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('municipality.categories'));

        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_municipality_user_can_delete_own_empty_category(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $category = $this->createCategory($this->office, 'Empty Category');

        $response = $this->actingAs($user)->delete(route('municipality.categories.destroy', $category));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('municipality.categories'));

        $this->assertDatabaseMissing('service_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_municipality_user_cannot_delete_category_with_services(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $category = $this->createCategory($this->office, 'Used Category');

        Service::create([
            'government_office_id' => $this->office->id,
            'service_category_id' => $category->id,
            'name' => 'Building Permit',
            'price' => 10,
            'duration_days' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('municipality.categories.destroy', $category));

        $response->assertRedirect(route('municipality.categories'));
        $response->assertSessionHas('error', 'Cannot delete this category because it still has services.');

        $this->assertDatabaseHas('service_categories', [
            'id' => $category->id,
        ]);
    }

    public function test_municipality_user_cannot_access_another_office_category(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $otherCategory = $this->createCategory($this->otherOffice, 'Other Office Category');

        $this->actingAs($user)
            ->get(route('municipality.categories.edit', $otherCategory))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(route('municipality.categories.update', $otherCategory), [
                'name' => 'Should Not Update',
                'description' => 'Blocked',
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('municipality.categories.destroy', $otherCategory))
            ->assertNotFound();

        $this->assertDatabaseHas('service_categories', [
            'id' => $otherCategory->id,
            'name' => 'Other Office Category',
        ]);
    }

    public function test_citizen_cannot_access_category_pages(): void
    {
        $citizen = $this->createUser('citizen@example.com', $this->citizenRole);
        $category = $this->createCategory($this->office, 'Permits');

        $this->actingAs($citizen)
            ->get(route('municipality.categories'))
            ->assertForbidden();

        $this->actingAs($citizen)
            ->post(route('municipality.categories.store'), [
                'name' => 'Blocked',
                'description' => 'Blocked',
            ])
            ->assertForbidden();

        $this->actingAs($citizen)
            ->get(route('municipality.categories.edit', $category))
            ->assertForbidden();

        $this->actingAs($citizen)
            ->put(route('municipality.categories.update', $category), [
                'name' => 'Blocked',
                'description' => 'Blocked',
            ])
            ->assertForbidden();

        $this->actingAs($citizen)
            ->delete(route('municipality.categories.destroy', $category))
            ->assertForbidden();
    }

    public function test_municipality_user_can_search_categories(): void
    {
        $user = $this->createUser('municipality@example.com', $this->municipalityRole, $this->office);
        $this->createCategory($this->office, 'Matching Category', 'Civil documents');
        $this->createCategory($this->office, 'Hidden Category', 'Other services');

        $response = $this->actingAs($user)->get(route('municipality.categories', [
            'search' => 'Civil',
        ]));

        $response->assertOk();
        $response->assertSee('Matching Category');
        $response->assertDontSee('Hidden Category');
        $response->assertSee('Clear');
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

    private function createCategory(GovernmentOffice $office, string $name, ?string $description = null): ServiceCategory
    {
        return ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => $name,
            'description' => $description,
        ]);
    }
}
