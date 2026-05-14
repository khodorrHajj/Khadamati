<?php

namespace App\Http\Controllers\Municipality;

use App\Http\Controllers\Controller;
use App\Models\GovernmentOffice;
use App\Models\GovernmentOfficeWorkingHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OfficeProfileController extends Controller
{
    protected array $days = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ];

    public function show()
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $office->load('municipality', 'workingHours');
        $workingHours = $this->buildWorkingHoursMap($office);

        return view('Municipality.office.show', compact('office', 'workingHours'));
    }

    public function edit()
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $office->load('municipality', 'workingHours');
        $days = $this->days;
        $workingHours = $this->buildWorkingHoursMap($office);

        return view('Municipality.office.edit', compact('office', 'days', 'workingHours'));
    }

    public function update(Request $request)
    {
        $office = $this->assignedOffice();

        if (!$office) {
            return view('Municipality.NoOffice');
        }

        $validated = $this->validateOffice($request);

        $office->update($validated);

        if ($request->has('working_hours')) {
            $office->workingHours()->delete();
            $this->saveWorkingHours($office, $request->input('working_hours', []));
        }

        return redirect()
            ->route('municipality.office.show')
            ->with('success', 'Office profile updated successfully.');
    }

    private function assignedOffice(): ?GovernmentOffice
    {
        return Auth::user()->governmentOffice;
    }

    private function validateOffice(Request $request): array
    {
        $validated = $request->validate([
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
}
