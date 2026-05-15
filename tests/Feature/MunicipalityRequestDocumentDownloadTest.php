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

class MunicipalityRequestDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipality_user_can_download_documents_for_own_office_request(): void
    {
        Storage::fake('public');

        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office);
        $document = $this->documentForRequest($serviceRequest, 'resident-id.pdf');

        Storage::disk('public')->put($document->document_path, 'fake pdf contents');

        $this->actingAs($municipalityUser)
            ->get(route('municipality.requests.documents.download', [$serviceRequest, $document]))
            ->assertOk()
            ->assertDownload('resident-id.pdf');
    }

    public function test_municipality_user_cannot_download_documents_for_another_office_request(): void
    {
        Storage::fake('public');

        $ownOffice = $this->office();
        $otherOffice = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $ownOffice->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($otherOffice);
        $document = $this->documentForRequest($serviceRequest);

        Storage::disk('public')->put($document->document_path, 'fake pdf contents');

        $this->actingAs($municipalityUser)
            ->get(route('municipality.requests.documents.download', [$serviceRequest, $document]))
            ->assertNotFound();
    }

    public function test_citizen_cannot_use_municipality_document_download_route(): void
    {
        $serviceRequest = $this->serviceRequestForOffice($this->office());
        $document = $this->documentForRequest($serviceRequest);
        $citizen = $this->userWithRole('citizen');

        $this->actingAs($citizen)
            ->get(route('municipality.requests.documents.download', [$serviceRequest, $document]))
            ->assertForbidden();
    }

    public function test_admin_cannot_use_municipality_document_download_route(): void
    {
        $serviceRequest = $this->serviceRequestForOffice($this->office());
        $document = $this->documentForRequest($serviceRequest);
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)
            ->get(route('municipality.requests.documents.download', [$serviceRequest, $document]))
            ->assertForbidden();
    }

    public function test_missing_file_returns_not_found_response(): void
    {
        Storage::fake('public');

        $office = $this->office();
        $municipalityUser = $this->userWithRole('municipality', [
            'government_office_id' => $office->id,
        ]);
        $serviceRequest = $this->serviceRequestForOffice($office);
        $document = $this->documentForRequest($serviceRequest);

        $this->actingAs($municipalityUser)
            ->get(route('municipality.requests.documents.download', [$serviceRequest, $document]))
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

    private function serviceRequestForOffice(GovernmentOffice $office): ServiceRequest
    {
        $citizen = $this->userWithRole('citizen');
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

        return ServiceRequest::create([
            'user_id' => $citizen->id,
            'service_id' => $service->id,
            'status' => ServiceRequest::STATUS_PENDING,
        ]);
    }

    private function documentForRequest(ServiceRequest $serviceRequest, string $originalName = 'document.pdf'): RequestDocument
    {
        return RequestDocument::create([
            'service_request_id' => $serviceRequest->id,
            'uploaded_by' => $serviceRequest->user_id,
            'document_path' => 'request-documents/' . uniqid() . '.pdf',
            'original_name' => $originalName,
            'document_type' => 'ID Copy',
        ]);
    }
}
