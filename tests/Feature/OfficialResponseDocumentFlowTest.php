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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficialResponseDocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipality_can_upload_official_response_for_own_office_request(): void
    {
        Storage::fake('public');

        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'notes' => 'Your document is ready.',
                'official_response_document_type' => 'Completion Certificate',
                'official_response' => UploadedFile::fake()->create('response.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame($municipalityUser->id, $serviceRequest->official_response_uploaded_by);
        $this->assertSame('response.pdf', $serviceRequest->official_response_original_name);
        $this->assertSame('Completion Certificate', $serviceRequest->official_response_document_type);
        $this->assertSame(ServiceRequest::STATUS_COMPLETED, $serviceRequest->status);
        Storage::disk('public')->assertExists($serviceRequest->official_response_path);
    }

    public function test_municipality_cannot_upload_official_response_for_another_office_request(): void
    {
        Storage::fake('public');

        $ownOffice = $this->office();
        $otherOffice = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $ownOffice->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($otherOffice);

        $this->actingAs($municipalityUser)
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'notes' => 'Blocked.',
                'official_response' => UploadedFile::fake()->create('response.pdf', 200, 'application/pdf'),
            ])
            ->assertNotFound();

        $this->assertNull($serviceRequest->fresh()->official_response_path);
    }

    public function test_citizen_can_download_official_response_for_own_request(): void
    {
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), $citizen, [
            'official_response_path' => 'official-responses/' . uniqid() . '.pdf',
            'official_response_original_name' => 'official-response.pdf',
        ]);
        Storage::disk('public')->put($serviceRequest->official_response_path, 'official response');

        $this->actingAs($citizen)
            ->get(route('citizen.requests.official-response.download', $serviceRequest))
            ->assertOk()
            ->assertDownload('official-response.pdf');
    }

    public function test_citizen_cannot_download_official_response_for_another_citizens_request(): void
    {
        Storage::fake('public');

        $citizen = $this->userWithRole('citizen');
        $otherCitizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), $otherCitizen, [
            'official_response_path' => 'official-responses/' . uniqid() . '.pdf',
            'official_response_original_name' => 'official-response.pdf',
        ]);
        Storage::disk('public')->put($serviceRequest->official_response_path, 'official response');

        $this->actingAs($citizen)
            ->get(route('citizen.requests.official-response.download', $serviceRequest))
            ->assertNotFound();
    }

    public function test_invalid_official_response_file_type_is_rejected(): void
    {
        Storage::fake('public');

        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office);

        $this->actingAs($municipalityUser)
            ->from(route('municipality.requests.show', $serviceRequest))
            ->put(route('municipality.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'notes' => 'Invalid upload.',
                'official_response' => UploadedFile::fake()->create('response.exe', 10, 'application/octet-stream'),
            ])
            ->assertRedirect(route('municipality.requests.show', $serviceRequest))
            ->assertSessionHasErrors('official_response');

        $this->assertNull($serviceRequest->fresh()->official_response_path);
    }

    public function test_missing_official_response_file_returns_not_found(): void
    {
        Storage::fake('public');

        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office, null, [
            'official_response_path' => 'official-responses/missing.pdf',
            'official_response_original_name' => 'missing.pdf',
        ]);

        $this->actingAs($municipalityUser)
            ->get(route('municipality.requests.official-response.download', $serviceRequest))
            ->assertNotFound();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['role' => $role]);

        return User::create(array_merge([
            'name' => ucfirst($role) . ' User',
            'email' => $role . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleModel->id,
            'is_active' => true,
        ], $attributes));
    }

    private function office(): GovernmentOffice
    {
        $municipality = Municipality::create([
            'name' => 'Municipality ' . uniqid(),
        ]);

        return GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => 'Office ' . uniqid(),
            'status' => 'active',
        ]);
    }

    private function serviceRequestForOffice(
        GovernmentOffice $office,
        ?User $citizen = null,
        array $attributes = []
    ): ServiceRequest {
        $citizen ??= $this->userWithRole('citizen');

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
            'status' => ServiceRequest::STATUS_PENDING,
        ], $attributes));
    }
}
