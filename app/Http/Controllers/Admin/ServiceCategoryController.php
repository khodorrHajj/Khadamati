<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\Municipality;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = ServiceCategory::with(['governmentOffice.municipality', 'services']);

        // Filter by municipality
        if ($request->filled('municipality')) {
            $query->whereHas('governmentOffice', function ($q) use ($request) {
                $q->where('municipality_id', $request->municipality);
            });
        }

        // Filter by office
        if ($request->filled('office')) {
            $query->where('government_office_id', $request->office);
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query->withCount('services')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $municipalities = Municipality::orderBy('name')->get();
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();

        // Stats
        $allCategories = ServiceCategory::all();
        $stats = [
            'total' => $allCategories->count(),
            'offices_with_categories' => $allCategories->pluck('government_office_id')->unique()->count(),
            'total_services' => $allCategories->sum(fn ($c) => $c->services()->count()),
        ];

        return view('Admin.categories.index', compact(
            'categories',
            'stats',
            'municipalities',
            'offices'
        ));
    }

    public function create(): View
    {
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();

        return view('Admin.categories.create', compact('offices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'government_office_id' => ['required', 'exists:government_offices,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        ServiceCategory::create($validated);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Service category created successfully.');
    }

    public function show(ServiceCategory $category): View
    {
        $category->load(['governmentOffice.municipality', 'services' => function ($q) {
            $q->orderBy('name');
        }]);

        return view('Admin.categories.show', compact('category'));
    }

    public function edit(ServiceCategory $category): View
    {
        $offices = GovernmentOffice::with('municipality')->orderBy('name')->get();

        return view('Admin.categories.edit', compact('category', 'offices'));
    }

    public function update(Request $request, ServiceCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'government_office_id' => ['required', 'exists:government_offices,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $category->update($validated);

        return redirect()->route('admin.categories.show', $category)
            ->with('success', 'Service category updated successfully.');
    }

    public function destroy(ServiceCategory $category): RedirectResponse
    {
        if ($category->services()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('error', 'Cannot delete category that has services. Please reassign or delete the services first.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Service category deleted successfully.');
    }
}
