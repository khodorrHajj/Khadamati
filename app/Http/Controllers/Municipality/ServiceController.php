<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category' => [
                'nullable',
                Rule::exists('service_categories', 'id')->where(function ($query) use ($office) {
                    $query->where('government_office_id', $office->id);
                }),
            ],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $search = $validated['search'] ?? null;
        $selectedCategory = $validated['category'] ?? null;
        $status = $validated['status'] ?? null;

        $categories = ServiceCategory::where('government_office_id', $office->id)
            ->orderBy('name')
            ->get();
        $requiredDocumentPresets = collect(config('required_documents.presets', []))
            ->unique()
            ->values();

        $services = Service::with('serviceCategory')
            ->where('government_office_id', $office->id)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($selectedCategory, function ($query) use ($selectedCategory, $office) {
                $query->whereHas('serviceCategory', function ($categoryQuery) use ($selectedCategory, $office) {
                    $categoryQuery->where('id', $selectedCategory)
                        ->where('government_office_id', $office->id);
                });
            })
            ->when($status, function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Municipality.Services', compact(
            'categories',
            'office',
            'requiredDocumentPresets',
            'search',
            'selectedCategory',
            'services',
            'status'
        ));
    }

    public function store(Request $request)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $validated = $this->validateService($request, $office);

        Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $validated['service_category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'duration_days_max' => $validated['duration_days_max'],
            'required_documents' => $this->normalizeRequiredDocuments($validated),
            'is_active' => true,
        ]);

        return redirect()
            ->route('municipality.services')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $this->authorizeService($service, $office);

        $categories = ServiceCategory::where('government_office_id', $office->id)
            ->orderBy('name')
            ->get();
        $requiredDocumentPresets = collect(config('required_documents.presets', []))
            ->unique()
            ->values();

        return view('Municipality.services.edit', compact('categories', 'office', 'requiredDocumentPresets', 'service'));
    }

    public function update(Request $request, Service $service)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeService($service, $office);

        $validated = $this->validateService($request, $office);

        $service->update([
            'service_category_id' => $validated['service_category_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'],
            'duration_days_max' => $validated['duration_days_max'],
            'required_documents' => $this->normalizeRequiredDocuments($validated),
        ]);

        return redirect()
            ->route('municipality.services')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeService($service, $office);

        $service->delete();

        return redirect()
            ->route('municipality.services')
            ->with('success', 'Service deleted successfully.');
    }

    public function toggleStatus(Service $service)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeService($service, $office);

        $service->update([
            'is_active' => !$service->is_active,
        ]);

        return redirect()
            ->route('municipality.services')
            ->with('success', 'Service status updated successfully.');
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

    private function authorizeService(Service $service, GovernmentOffice $office): void
    {
        abort_if($service->government_office_id !== $office->id, 404);
    }

    private function validateService(Request $request, GovernmentOffice $office): array
    {
        return $request->validate([
            'service_category_id' => [
                'required',
                Rule::exists('service_categories', 'id')->where(function ($query) use ($office) {
                    $query->where('government_office_id', $office->id);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'duration_days_max' => ['required', 'integer', 'gte:duration_days'],
            'required_documents' => ['nullable', 'string'],
            'required_documents_list' => ['nullable', 'array'],
            'required_documents_list.*' => ['string', 'max:255'],
        ]);
    }

    private function normalizeRequiredDocuments(array $validated): ?string
    {
        $documents = collect($validated['required_documents_list'] ?? [])
            ->merge(
                collect(preg_split(
                    '/\r\n|\r|\n/',
                    (string) ($validated['required_documents'] ?? ''),
                    -1,
                    PREG_SPLIT_NO_EMPTY
                ))
            )
            ->map(fn ($document) => trim((string) $document))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return empty($documents)
            ? null
            : json_encode($documents, JSON_UNESCAPED_UNICODE);
    }
}
