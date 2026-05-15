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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRequestMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_request_index_and_detail_pages(): void
    {
        $admin = $this->userWithRole('admin');
        $serviceRequest = $this->serviceRequestForOffice($this->office());

        $this->actingAs($admin)
            ->get(route('admin.requests.index'))
            ->assertOk()
            ->assertSee($serviceRequest->tracking_code);

        $this->actingAs($admin)
            ->get(route('admin.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('Request #' . $serviceRequest->id)
            ->assertSee($serviceRequest->user->email);
    }

    public function test_admin_poll_endpoint_returns_requests_html_for_live_refresh(): void
    {
        $admin = $this->userWithRole('admin');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), null, [
            'tracking_code' => 'REQ-LIVE-POLL',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.requests.poll'))
            ->assertOk()
            ->assertJsonStructure([
                'table_html',
                'pagination_html',
                'count',
            ])
            ->assertJsonFragment([
                'count' => 1,
            ]);

        $response = $this->actingAs($admin)->getJson(route('admin.requests.poll'));
        $this->assertStringContainsString($serviceRequest->tracking_code, $response->json('table_html'));
    }

    public function test_non_admin_users_cannot_access_admin_request_routes(): void
    {
        $serviceRequest = $this->serviceRequestForOffice($this->office());
        $document = $this->documentForRequest($serviceRequest);
        $citizen = $this->userWithRole('citizen');
        $municipalityUser = $this->userWithRole('municipality');

        $this->actingAs($citizen)
            ->get(route('admin.requests.index'))
            ->assertForbidden();

        $this->actingAs($municipalityUser)
            ->get(route('admin.requests.show', $serviceRequest))
            ->assertForbidden();

        $this->actingAs($citizen)
            ->get(route('admin.requests.documents.download', [$serviceRequest, $document]))
            ->assertForbidden();
    }

    public function test_admin_request_filters_work(): void
    {
        $admin = $this->userWithRole('admin');
        $targetOffice = $this->office('Target Municipality', 'Records Office');
        $otherOffice = $this->office('Other Municipality', 'Licensing Office');

        $targetCategory = ServiceCategory::create([
            'government_office_id' => $targetOffice->id,
            'name' => 'Target Category',
        ]);
        $otherCategory = ServiceCategory::create([
            'government_office_id' => $otherOffice->id,
            'name' => 'Other Category',
        ]);

        $targetService = Service::create([
            'government_office_id' => $targetOffice->id,
            'service_category_id' => $targetCategory->id,
            'name' => 'Target Service',
            'price' => 12,
            'duration_days' => 3,
            'is_active' => true,
        ]);
        $otherService = Service::create([
            'government_office_id' => $otherOffice->id,
            'service_category_id' => $otherCategory->id,
            'name' => 'Other Service',
            'price' => 20,
            'duration_days' => 4,
            'is_active' => true,
        ]);

        $matchingCitizen = $this->userWithRole('citizen', [
            'name' => 'Alice Match',
            'email' => 'alice.match@example.com',
        ]);
        $hiddenCitizen = $this->userWithRole('citizen', [
            'name' => 'Bob Hidden',
            'email' => 'bob.hidden@example.com',
        ]);

        $matchingRequest = ServiceRequest::create([
            'user_id' => $matchingCitizen->id,
            'service_id' => $targetService->id,
            'status' => ServiceRequest::STATUS_APPROVED,
            'tracking_code' => 'REQ-FILTER-MATCH',
        ]);
        $matchingRequest->timestamps = false;
        $matchingRequest->update([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $hiddenRequest = ServiceRequest::create([
            'user_id' => $hiddenCitizen->id,
            'service_id' => $otherService->id,
            'status' => ServiceRequest::STATUS_PENDING,
            'tracking_code' => 'REQ-FILTER-HIDDEN',
        ]);
        $hiddenRequest->timestamps = false;
        $hiddenRequest->update([
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests.index', [
                'status' => ServiceRequest::STATUS_APPROVED,
                'municipality' => $targetOffice->municipality_id,
                'office' => $targetOffice->id,
                'service' => $targetService->id,
                'category' => $targetCategory->id,
                'date_from' => now()->subDays(2)->toDateString(),
                'date_to' => now()->toDateString(),
                'search' => 'Alice Match',
                'tracking_code' => 'MATCH',
            ]))
            ->assertOk()
            ->assertSee($matchingRequest->tracking_code)
            ->assertDontSee('REQ-FILTER-HIDDEN')
            ->assertDontSee('bob.hidden@example.com');
    }

    public function test_admin_can_update_status_and_internal_note(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('admin');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), null, [
            'message' => 'Citizen-visible note',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_COMPLETED,
                'admin_internal_note' => 'Admin-only follow-up.',
                'official_response_document_type' => 'Completion Certificate',
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame(ServiceRequest::STATUS_COMPLETED, $serviceRequest->status);
        $this->assertSame('Admin-only follow-up.', $serviceRequest->admin_internal_note);
        $this->assertSame('Citizen-visible note', $serviceRequest->message);
    }

    public function test_admin_can_upload_and_replace_official_response_files(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('admin');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), null, [
            'official_response_path' => 'official-responses/old-response.pdf',
            'official_response_original_name' => 'old-response.pdf',
        ]);

        Storage::disk('public')->put('official-responses/old-response.pdf', 'old');

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_APPROVED,
                'admin_internal_note' => 'Uploaded replacement.',
                'official_response_document_type' => 'Approval Letter',
                'official_response' => UploadedFile::fake()->create('new-response.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame($admin->id, $serviceRequest->official_response_uploaded_by);
        $this->assertSame('new-response.pdf', $serviceRequest->official_response_original_name);
        $this->assertSame('Approval Letter', $serviceRequest->official_response_document_type);
        Storage::disk('public')->assertMissing('official-responses/old-response.pdf');
        Storage::disk('public')->assertExists($serviceRequest->official_response_path);
    }

    public function test_admin_can_generate_official_response_pdf(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('admin');
        $serviceRequest = $this->serviceRequestForOffice($this->office());

        $this->actingAs($admin)
            ->put(route('admin.requests.update', $serviceRequest), [
                'status' => ServiceRequest::STATUS_APPROVED,
                'admin_internal_note' => 'Generated response.',
                'official_response_document_type' => 'Approval Summary',
                'generate_official_response_pdf' => '1',
                'official_response_summary' => 'The platform has approved this request and generated this PDF as the official response.',
            ])
            ->assertRedirect(route('admin.requests.show', $serviceRequest));

        $serviceRequest->refresh();

        $this->assertSame($admin->id, $serviceRequest->official_response_uploaded_by);
        $this->assertSame($serviceRequest->tracking_code . '-official-response.pdf', $serviceRequest->official_response_original_name);
        $this->assertSame('Approval Summary', $serviceRequest->official_response_document_type);
        Storage::disk('public')->assertExists($serviceRequest->official_response_path);
    }

    public function test_admin_can_download_request_documents_and_official_response(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole('admin');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), null, [
            'official_response_path' => 'official-responses/' . uniqid() . '.pdf',
            'official_response_original_name' => 'official-response.pdf',
        ]);
        $document = $this->documentForRequest($serviceRequest, 'citizen-document.pdf');

        Storage::disk('public')->put($document->document_path, 'document');
        Storage::disk('public')->put($serviceRequest->official_response_path, 'response');

        $this->actingAs($admin)
            ->get(route('admin.requests.documents.download', [$serviceRequest, $document]))
            ->assertOk()
            ->assertDownload('citizen-document.pdf');

        $this->actingAs($admin)
            ->get(route('admin.requests.official-response.download', $serviceRequest))
            ->assertOk()
            ->assertDownload('official-response.pdf');
    }

    public function test_admin_internal_note_is_visible_in_admin_only(): void
    {
        $admin = $this->userWithRole('admin');
        $citizen = $this->userWithRole('citizen');
        $serviceRequest = $this->serviceRequestForOffice($this->office(), $citizen, [
            'admin_internal_note' => 'Sensitive admin note',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests.show', $serviceRequest))
            ->assertOk()
            ->assertSee('Sensitive admin note');

        $this->actingAs($citizen)
            ->get(route('citizen.requests.show', $serviceRequest))
            ->assertOk()
            ->assertDontSee('Sensitive admin note');

        $this->get(route('tracking.show', $serviceRequest->tracking_code))
            ->assertOk()
            ->assertDontSee('Sensitive admin note');
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $roleModel = Role::firstOrCreate(['role' => $role]);

        return User::create(array_merge([
            'name' => ucfirst($role) . ' User ' . uniqid(),
            'email' => $role . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleModel->id,
            'is_active' => true,
            'status' => 'active',
        ], $attributes));
    }

    private function office(?string $municipalityName = null, ?string $officeName = null): GovernmentOffice
    {
        $municipality = Municipality::create([
            'name' => $municipalityName ?: 'Municipality ' . uniqid(),
            'status' => 'active',
        ]);

        return GovernmentOffice::create([
            'municipality_id' => $municipality->id,
            'name' => $officeName ?: 'Office ' . uniqid(),
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
            'name' => 'Certificates ' . uniqid(),
        ]);

        $service = Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $category->id,
            'name' => 'Birth Certificate ' . uniqid(),
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
