<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\MunicipalityWorkingHour;
use Illuminate\Http\Request;

class MunicipalityController extends Controller
{
    protected $days = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ];

    public function index(Request $request)
    {
        $query = Municipality::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $municipalities = $query->latest()->paginate(10)->withQueryString();

        return view('admin.municipalities.index', compact('municipalities'));
    }

    public function create()
    {
        $days = $this->days;
        return view('admin.municipalities.create', compact('days'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => ['required', 'string', 'max:50', 'regex:/^(?:0(?:1|3|5|70|71|76|78|79|81)\d{6}|\+961(?:1|3|5|70|71|76|78|79|81)\d{6})$/'],
            'email'           => 'nullable|email|max:255',
            'city'            => 'required|string|max:255',
            'street'          => 'required|string|max:255',
            'building'        => 'nullable|string|max:255',
            'google_maps_url' => 'nullable|url|max:1000',
            'status'          => 'required|in:active,inactive',
            'notes'           => 'nullable|string',
        ], [
            'phone.regex' => 'Please enter a valid Lebanese phone number.',
        ]);

        $hoursInput = $request->input('working_hours', []);
        foreach ($this->days as $day) {
            $dayData = $hoursInput[$day] ?? [];
            $request->validate([
                "working_hours.{$day}.is_open" => 'required|boolean',
                "working_hours.{$day}.start_time" => 'nullable|date_format:H:i',
                "working_hours.{$day}.end_time" => 'nullable|date_format:H:i',
            ]);

            $isOpen  = isset($dayData['is_open']) && $dayData['is_open'];

            if ($isOpen) {
                $request->validate([
                    "working_hours.{$day}.start_time" => 'required|date_format:H:i',
                    "working_hours.{$day}.end_time"   => 'required|date_format:H:i',
                ], [
                    "working_hours.{$day}.start_time.required" => "{$day}: start time is required when day is open.",
                    "working_hours.{$day}.end_time.required"   => "{$day}: end time is required when day is open.",
                ]);

                if (
                    !empty($dayData['start_time']) &&
                    !empty($dayData['end_time']) &&
                    $dayData['end_time'] <= $dayData['start_time']
                ) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            "working_hours.{$day}.end_time" =>
                                "{$day}: end time must be after start time."
                        ]);
                }
            }
        }

        $municipality = Municipality::create($validated);
        $this->saveWorkingHours($municipality, $hoursInput);

        return redirect()
            ->route('admin.municipalities.index')
            ->with('success', 'Municipality created successfully.');
    }
    

    public function show(Municipality $municipality)
    {
        $municipality->load('workingHours');
        $workingHours = $this->buildWorkingHoursMap($municipality);

        return view('admin.municipalities.show', compact('municipality', 'workingHours'));
    }

    public function edit(Municipality $municipality)
    {
        $municipality->load('workingHours');
        $days = $this->days;
        $workingHours = $this->buildWorkingHoursMap($municipality);

        return view('admin.municipalities.edit', compact('municipality', 'days', 'workingHours'));
    }

    public function update(Request $request, Municipality $municipality)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'city'            => 'nullable|string|max:255',
            'street'          => 'nullable|string|max:255',
            'building'        => 'nullable|string|max:255',
            'google_maps_url' => 'nullable|url|max:2048',
            'status'          => 'required|in:active,inactive',
            'notes'           => 'nullable|string',
        ]);

        // Validate working hours when day is open
        $hoursInput = $request->input('working_hours', []);
        foreach ($this->days as $day) {
            $dayData = $hoursInput[$day] ?? [];
            $isOpen  = isset($dayData['is_open']) && $dayData['is_open'];

            if ($isOpen) {
                $request->validate([
                    "working_hours.{$day}.start_time" => 'required',
                    "working_hours.{$day}.end_time"   => 'required',
                ], [
                    "working_hours.{$day}.start_time.required" => "{$day}: start time is required when day is open.",
                    "working_hours.{$day}.end_time.required"   => "{$day}: end time is required when day is open.",
                ]);

                if (
                    !empty($dayData['start_time']) &&
                    !empty($dayData['end_time']) &&
                    $dayData['end_time'] <= $dayData['start_time']
                ) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            "working_hours.{$day}.end_time" =>
                                "{$day}: end time must be after start time."
                        ]);
                }
            }
        }

        $municipality->update($validated);
        $municipality->workingHours()->delete();
        $this->saveWorkingHours($municipality, $hoursInput);

        return redirect()
            ->route('admin.municipalities.show', $municipality)
            ->with('success', 'Municipality updated successfully.');
    }

    public function destroy(Municipality $municipality)
    {
        $officesCount = $municipality->governmentOffices()->count();

        if ($officesCount > 0) {
            return redirect()
                ->route('admin.municipalities.show', $municipality)
                ->with('error', 'Cannot delete this municipality because it has related offices or users.');
        }

        $municipality->workingHours()->delete();
        $municipality->delete();

        return redirect()
            ->route('admin.municipalities.index')
            ->with('success', 'Municipality deleted successfully.');
    }


    private function saveWorkingHours(Municipality $municipality, array $hoursInput)
    {
        foreach ($this->days as $day) {
            $dayData  = $hoursInput[$day] ?? [];
            $isOpen   = isset($dayData['is_open']) && (bool)$dayData['is_open'];
            $start    = $isOpen ? ($dayData['start_time'] ?? null) : null;
            $end      = $isOpen ? ($dayData['end_time']   ?? null) : null;

            MunicipalityWorkingHour::create([
                'municipality_id' => $municipality->id,
                'day_of_week'     => $day,
                'is_open'         => $isOpen,
                'start_time'      => $start,
                'end_time'        => $end,
            ]);
        }
    }

    private function buildWorkingHoursMap(Municipality $municipality): array
    {
        $defaults = [
            'Monday'    => true,
            'Tuesday'   => true,
            'Wednesday' => true,
            'Thursday'  => true,
            'Friday'    => true,
            'Saturday'  => false,
            'Sunday'    => false,
        ];

        $map = [];
        $existing = $municipality->workingHours->keyBy('day_of_week');

        foreach ($this->days as $day) {
            if ($existing->has($day)) {
                $map[$day] = $existing[$day];
            } else {
                $map[$day] = (object)[
                    'day_of_week' => $day,
                    'is_open'     => $defaults[$day],
                    'start_time'  => '08:00',
                    'end_time'    => '14:00',
                ];
            }
        }

        return $map;
    }
}
