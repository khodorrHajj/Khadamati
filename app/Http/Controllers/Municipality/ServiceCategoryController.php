<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = $validated['search'] ?? null;

        $categories = ServiceCategory::where('government_office_id', $office->id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($categoryQuery) use ($search) {
                    $categoryQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('Municipality.Categories', compact('categories', 'office', 'search'));
    }

    public function store(Request $request)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $validated = $this->validateCategory($request);

        ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('municipality.categories')
            ->with('success', 'Category created successfully.');
    }

    public function edit(ServiceCategory $category)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $this->authorizeCategory($category, $office);

        return view('Municipality.categories.edit', compact('category', 'office'));
    }

    public function update(Request $request, ServiceCategory $category)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeCategory($category, $office);

        $category->update($this->validateCategory($request));

        return redirect()
            ->route('municipality.categories')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(ServiceCategory $category)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return abort(403);
        }

        $this->authorizeCategory($category, $office);

        if ($category->services()->exists()) {
            return redirect()
                ->route('municipality.categories')
                ->with('error', 'Cannot delete this category because it still has services.');
        }

        $category->delete();

        return redirect()
            ->route('municipality.categories')
            ->with('success', 'Category deleted successfully.');
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

    private function authorizeCategory(ServiceCategory $category, GovernmentOffice $office): void
    {
        abort_if($category->government_office_id !== $office->id, 404);
    }

    private function validateCategory(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
