<?php

namespace Tests\Feature;

use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\RequestDocument;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CitizenMyRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_citizen_sees_only_own_requests(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $ownRequest = $this->serviceRequestFor($citizen, ['tracking_code' => 'REQ-OWN-123']);
        $otherRequest = $this->serviceRequestFor($otherCitizen, ['tracking_code' => 'REQ-OTHER-456']);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.index'))
            ->assertOk()
            ->assertSee($ownRequest->tracking_code)
            ->assertDontSee($otherRequest->tracking_code);
    }

    public function test_citizen_can_view_own_request_details(): void
    {
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen, [
            'tracking_code' => 'REQ-DETAIL-123',
            'message' => 'Please handle carefully.',
        ]);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('REQ-DETAIL-123')
            ->assertSee($serviceRequest->service->name)
            ->assertSee($serviceRequest->service->governmentOffice->name)
            ->assertSee($serviceRequest->service->governmentOffice->municipality->name)
            ->assertSee('Please handle carefully.');
    }

    public function test_citizen_cannot_view_another_citizens_request(): void
    {
        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $otherRequest = $this->serviceRequestFor($otherCitizen);

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $otherRequest))
            ->assertNotFound();
    }

    public function test_citizen_can_download_own_uploaded_documents(): void
    {
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen);
        $document = RequestDocument::create([
            'service_request_id' => $serviceRequest->id,
            'uploaded_by' => $citizen->id,
            'document_path' => 'request-documents/' . uniqid() . '.pdf',
            'original_name' => 'id-copy.pdf',
            'document_type' => 'ID Copy',
        ]);

        Storage::disk('public')->put($document->document_path, 'fake pdf contents');

        $this->actingAs($citizen)
            ->get(route('citizen.requests.documents.download', [$serviceRequest, $document]))
            ->assertOk()
            ->assertDownload('id-copy.pdf');
    }

    public function test_citizen_can_download_official_response_documents_for_own_request(): void
    {
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestFor($citizen, [
            'official_response_path' => 'official-responses/' . uniqid() . '.pdf',
            'official_response_original_name' => 'official-response.pdf',
        ]);

        Storage::disk('public')->put($serviceRequest->official_response_path, 'official response contents');

        $this->actingAs($citizen)
            ->get(route('citizen.requests.official-response.download', $serviceRequest))
            ->assertOk()
            ->assertDownload('official-response.pdf');
    }

    public function test_filters_work(): void
    {
        $citizen = $this->userWithRole('citizen');
        $office = $this->office(['name' => 'Registry Office']);
        $otherOffice = $this->office(['name' => 'Tax Office']);
        $service = $this->service($office, ['name' => 'Birth Certificate']);
        $otherService = $this->service($otherOffice, ['name' => 'Tax Clearance']);

        $matchingRequest = $this->serviceRequestFor($citizen, [
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_COMPLETED,
            'tracking_code' => 'REQ-FILTER-MATCH',
        ]);
        $matchingRequest->forceFill(['created_at' => now()->subDay()])->save();

        $nonMatchingRequest = $this->serviceRequestFor($citizen, [
            'service_id' => $otherService->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'tracking_code' => 'REQ-FILTER-HIDDEN',
        ]);
        $nonMatchingRequest->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($citizen)
            ->get(route('citizen.requests.index', [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'service' => $service->id,
                'office' => $office->id,
                'date_from' => now()->subDays(2)->toDateString(),
                'date_to' => now()->toDateString(),
                'tracking_code' => 'MATCH',
            ]))
            ->assertOk()
            ->assertSee('REQ-FILTER-MATCH')
            ->assertDontSee('REQ-FILTER-HIDDEN');
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
            'name' => 'Municipality ' . uniqid(),
        ]);

        return GovernmentOffice::create(array_merge([
            'municipality_id' => $municipality->id,
            'name' => 'Office ' . uniqid(),
            'status' => 'active',
        ], $attributes));
    }

    private function service(GovernmentOffice $office, array $attributes = []): Service
    {
        $category = ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => 'Certificates',
        ]);

        return Service::create(array_merge([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate',
            'price' => 10,
            'duration_days' => 2,
            'is_active' => true,
        ], $attributes));
    }

    private function serviceRequestFor(User $citizen, array $attributes = []): ServiceRequest
    {
        $service = isset($attributes['service_id'])
            ? Service::findOrFail($attributes['service_id'])
            : $this->service($this->office());

        return ServiceRequest::create(array_merge([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'message' => 'Request notes',
        ], $attributes));
    }
}
