<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\GovernmentOfficeWorkingHour;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GovernmentOfficeController extends Controller
{
    protected $days = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    public function index(Request $request)
    {
        $query = GovernmentOffice::with('municipality');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('service_type', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('municipality', function ($municipalityQuery) use ($search) {
                        $municipalityQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $municipalities = $this->activeMunicipalities();
        $offices = $query->latest()->paginate(10)->withQueryString();

        return view('Admin.Offices', compact('municipalities', 'offices'));
    }

    public function create()
    {
        $municipalities = $this->activeMunicipalities();
        $days = $this->days;
        $workingHours = $this->defaultWorkingHours();

        return view('Admin.offices.create', compact('municipalities', 'days', 'workingHours'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateOffice($request);

        $office = GovernmentOffice::create($validated);

        $hoursInput = $request->input('working_hours');
        if (!$hoursInput) {
            $municipality = Municipality::with('workingHours')->find($validated['municipality_id']);
            $hoursInput = $this->defaultWorkingHoursFromMunicipality($municipality);
        }

        $this->saveWorkingHours($office, $hoursInput);

        return redirect()
            ->route('admin.offices.index')
            ->with('success', 'Government office created successfully.');
    }

    public function show(GovernmentOffice $office)
    {
        $office->load('municipality', 'workingHours');
        $workingHours = $this->buildWorkingHoursMap($office);

        return view('Admin.offices.show', compact('office', 'workingHours'));
    }

    public function edit(GovernmentOffice $office)
    {
        $office->load('workingHours');
        $municipalities = $this->activeMunicipalities();
        $days = $this->days;
        $workingHours = $this->buildWorkingHoursMap($office);

        return view('Admin.offices.edit', compact('office', 'municipalities', 'days', 'workingHours'));
    }

    public function update(Request $request, GovernmentOffice $office)
    {
        $validated = $this->validateOffice($request);

        $office->update($validated);

        if ($request->has('working_hours')) {
            $office->workingHours()->delete();
            $this->saveWorkingHours($office, $request->input('working_hours', []));
        }

        return redirect()
            ->route('admin.offices.show', $office)
            ->with('success', 'Government office updated successfully.');
    }

    public function destroy(GovernmentOffice $office)
    {
        if ($office->users()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete this government office because it has related users.');
        }

        $office->workingHours()->delete();
        $office->delete();

        return redirect()
            ->route('admin.offices.index')
            ->with('success', 'Government office deleted successfully.');
    }

    private function validateOffice(Request $request): array
    {
        $validated = $request->validate([
            'municipality_id' => 'required|exists:municipalities,id',
            'name' => 'required|string|max:255',
            'service_type' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'building' => 'nullable|string|max:255',
            'google_maps_url' => 'nullable|url|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'place_id' => 'nullable|string|max:255',
            'formatted_address' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ]);

        if ($request->has('working_hours')) {
            $this->validateWorkingHours($request);
        }

        return $validated;
    }

    private function validateWorkingHours(Request $request): void
    {
        foreach ($this->days as $day) {
            $dayData = $request->input("working_hours.{$day}", []);
            $isOpen = isset($dayData['is_open']) && $dayData['is_open'];

            $request->validate([
                "working_hours.{$day}.is_open" => 'required|boolean',
                "working_hours.{$day}.start_time" => 'nullable|date_format:H:i',
                "working_hours.{$day}.end_time" => 'nullable|date_format:H:i',
            ]);

            if ($isOpen) {
                $request->validate([
                    "working_hours.{$day}.start_time" => 'required|date_format:H:i',
                    "working_hours.{$day}.end_time" => 'required|date_format:H:i',
                ], [
                    "working_hours.{$day}.start_time.required" => "{$day}: start time is required when day is open.",
                    "working_hours.{$day}.end_time.required" => "{$day}: end time is required when day is open.",
                ]);

                if (
                    !empty($dayData['start_time']) &&
                    !empty($dayData['end_time']) &&
                    $dayData['end_time'] <= $dayData['start_time']
                ) {
                    throw ValidationException::withMessages([
                        "working_hours.{$day}.end_time" => "{$day}: end time must be after start time.",
                    ]);
                }
            }
        }
    }

    private function saveWorkingHours(GovernmentOffice $office, array $hoursInput): void
    {
        foreach ($this->days as $day) {
            $dayData = $hoursInput[$day] ?? [];
            $isOpen = isset($dayData['is_open']) && (bool) $dayData['is_open'];

            GovernmentOfficeWorkingHour::create([
                'government_office_id' => $office->id,
                'day_of_week' => $day,
                'is_open' => $isOpen,
                'start_time' => $isOpen ? ($dayData['start_time'] ?? null) : null,
                'end_time' => $isOpen ? ($dayData['end_time'] ?? null) : null,
            ]);
        }
    }

    private function activeMunicipalities()
    {
        return Municipality::where('status', 'active')->orderBy('name')->get();
    }

    private function defaultWorkingHoursFromMunicipality(?Municipality $municipality): array
    {
        if ($municipality && $municipality->workingHours->count()) {
            return $municipality->workingHours
                ->mapWithKeys(function ($workingHour) {
                    return [
                        $workingHour->day_of_week => [
                            'is_open' => $workingHour->is_open ? '1' : '0',
                            'start_time' => $workingHour->start_time,
                            'end_time' => $workingHour->end_time,
                        ],
                    ];
                })
                ->toArray();
        }

        return $this->defaultWorkingHours();
    }

    private function defaultWorkingHours(): array
    {
        $defaults = [];

        foreach ($this->days as $day) {
            $isOpen = in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);

            $defaults[$day] = [
                'is_open' => $isOpen ? '1' : '0',
                'start_time' => $isOpen ? '08:00' : null,
                'end_time' => $isOpen ? '14:00' : null,
            ];
        }

        return $defaults;
    }

    private function buildWorkingHoursMap(GovernmentOffice $office): array
    {
        if (!$office->relationLoaded('workingHours')) {
            $office->load('workingHours');
        }

        $existing = $office->workingHours->keyBy('day_of_week');
        $defaults = $this->defaultWorkingHours();
        $map = [];

        foreach ($this->days as $day) {
            $map[$day] = $existing->get($day) ?: (object) [
                'day_of_week' => $day,
                'is_open' => $defaults[$day]['is_open'] === '1',
                'start_time' => $defaults[$day]['start_time'],
                'end_time' => $defaults[$day]['end_time'],
            ];
        }

        return $map;
    }
}
