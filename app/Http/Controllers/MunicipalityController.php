<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MunicipalityController extends Controller
{
    public function dashboard()
    {
        return view('Municipality.Dashboard');
    }

    public function categories()
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $categories = ServiceCategory::where('government_office_id', $office->id)->get();

        return view('Municipality.Categories', compact('categories', 'office'));
    }

    public function storeCategory(Request $request)
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return abort(403);
        }

        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);

        ServiceCategory::create([
            'government_office_id' => $office->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function services()
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $categories = ServiceCategory::where('government_office_id', $office->id)->get();
        $services = Service::with('serviceCategory')
            ->where('government_office_id', $office->id)
            ->get();

        return view('Municipality.Services', compact('categories', 'services', 'office'));
    }

    public function storeService(Request $request)
    {
        $office = Auth::user()->governmentOffice;

        if (!$office) {
            return abort(403);
        }

        $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'required_documents' => 'nullable',
        ]);

        Service::create([
            'government_office_id' => $office->id,
            'service_category_id' => $request->service_category_id,
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration_days' => $request->duration_days,
            'required_documents' => $request->required_documents,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Service created successfully.');
    }
}