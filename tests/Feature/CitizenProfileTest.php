<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CitizenProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_view_profile_page(): void
    {
        $citizen = $this->citizenUser();

        $this->actingAs($citizen)
            ->get(route('citizen.profile.show'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee($citizen->name)
            ->assertSee($citizen->email)
            ->assertSee('Change Password')
            ->assertSee('Delete Account');
    }

    public function test_citizen_can_start_password_change_with_email_otp(): void
    {
        Mail::fake();

        $citizen = $this->citizenUser();

        $this->actingAs($citizen)
            ->post(route('citizen.profile.password.send-otp'), [
                'current_password' => 'password123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect(route('citizen.profile.show'))
            ->assertSessionHas('success');

        $pendingChange = session('citizen_password_change_otp');

        $this->assertIsArray($pendingChange);
        $this->assertSame($citizen->id, $pendingChange['user_id']);
        $this->assertTrue(Hash::check('newpassword123', $pendingChange['new_password_hash']));
        $this->assertTrue(Hash::check('password123', $citizen->fresh()->password));
    }

    public function test_citizen_can_confirm_password_change_with_valid_email_otp(): void
    {
        $citizen = $this->citizenUser();

        $this->actingAs($citizen)
            ->withSession([
                'citizen_password_change_otp' => [
                    'user_id' => $citizen->id,
                    'email' => $citizen->email,
                    'code_hash' => Hash::make('123456'),
                    'expires_at' => now()->addMinutes(10)->toDateTimeString(),
                    'new_password_hash' => Hash::make('updated-password'),
                ],
            ])
            ->post(route('citizen.profile.password.confirm-otp'), [
                'password_otp' => '123456',
            ])
            ->assertRedirect(route('citizen.profile.show'))
            ->assertSessionHas('success', 'Your password was updated successfully.');

        $this->assertTrue(Hash::check('updated-password', $citizen->fresh()->password));
    }

    public function test_citizen_can_delete_account_with_current_password(): void
    {
        $citizen = $this->citizenUser();

        $this->actingAs($citizen)
            ->delete(route('citizen.profile.destroy'), [
                'delete_password' => 'password123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Your account was deleted successfully.');

        $this->assertDatabaseMissing('users', [
            'id' => $citizen->id,
        ]);
        $this->assertGuest();
    }

    private function citizenUser(): User
    {
        $role = Role::firstOrCreate(['role' => 'citizen']);

        return User::create([
            'name' => 'Citizen User',
            'email' => 'citizen@example.com',
            'phone' => '70123456',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'status' => 'active',
            'is_active' => true,
            'two_factor_enabled' => false,
        ]);
    }
}
