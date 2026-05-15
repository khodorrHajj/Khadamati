<?php

namespace Tests\Feature;

use App\Models\Feedback;
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

class CitizenFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_can_submit_feedback_for_own_completed_request(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen, [
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.requests.feedback.store', $serviceRequest), [
                'rating' => 5,
                'comment' => 'Excellent service.',
            ])
            ->assertRedirect(route('citizen.requests.show', $serviceRequest));

        $this->assertDatabaseHas('feedback', [
            'service_request_id' => $serviceRequest->id,
            'user_id' => $citizen->id,
            'rating' => 5,
            'comment' => 'Excellent service.',
        ]);
    }

    public function test_citizen_cannot_submit_feedback_for_pending_or_in_review_request(): void
    {
        $citizen = $this->userWithRole('citizen');
        $pendingRequest = $this->serviceRequestFor($citizen, [
            'status' => ServiceRequest::STATUS_PENDING,
        ]);
        $inReviewRequest = $this->serviceRequestFor($citizen, [
            'status' => ServiceRequest::STATUS_IN_REVIEW,
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.requests.feedback.store', $pendingRequest), [
                'rating' => 4,
                'comment' => 'Too early.',
            ])
            ->assertForbidden();

        $this->actingAs($citizen)
            ->post(route('citizen.requests.feedback.store', $inReviewRequest), [
                'rating' => 4,
                'comment' => 'Still too early.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_citizen_cannot_submit_feedback_for_another_users_request(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $otherRequest = $this->serviceRequestFor($otherCitizen, [
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.requests.feedback.store', $otherRequest), [
                'rating' => 5,
                'comment' => 'Not mine.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen, [
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        $this->actingAs($citizen)
            ->from(route('citizen.requests.show', $serviceRequest))
            ->post(route('citizen.requests.feedback.store', $serviceRequest), [
                'rating' => 6,
                'comment' => 'Invalid rating.',
            ])
            ->assertRedirect(route('citizen.requests.show', $serviceRequest))
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_duplicate_feedback_is_prevented(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen, [
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        Feedback::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $citizen->id,
            'rating' => 4,
            'comment' => 'Already submitted.',
        ]);

        $this->actingAs($citizen)
            ->post(route('citizen.requests.feedback.store', $serviceRequest), [
                'rating' => 5,
                'comment' => 'Duplicate.',
            ])
            ->assertRedirect(route('citizen.requests.show', $serviceRequest))
            ->assertSessionHasErrors('feedback');

        $this->assertDatabaseCount('feedback', 1);
    }

    public function test_municipality_response_is_visible_to_correct_citizen(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen, [
            'status' => ServiceRequest::STATUS_COMPLETED,
        ]);

        Feedback::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => $citizen->id,
            'rating' => 5,
            'comment' => 'Great service.',
            'public_response' => 'Thank you for your feedback.',
            'private_response' => 'Your certificate is archived under case 123.',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('Great service.')
            ->assertSee('Thank you for your feedback.')
            ->assertSee('Your certificate is archived under case 123.');

        $this->actingAs($citizen)
            ->get(route('citizen.feedback.index'))
            ->assertOk()
            ->assertSee('Great service.')
            ->assertSee('Responded');
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

    private function serviceRequestFor(User $citizen, array $attributes = []): ServiceRequest
    {
        $municipality = Municipality::create([
            'name' => 'Municipality ' . uniqid(),
        ]);

        $office = GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Office ' . uniqid(),
            'status' => 'active',
        ]);

        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        $service = Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'price' => 10,
            'duration_days' => 2,
            'is_active' => true,
        ]);

        return ServiceRequest::create(array_merge([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_COMPLETED,
            'message' => 'Request notes',
        ], $attributes));
    }
}
