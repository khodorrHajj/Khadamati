<?php

namespace App\Http\Requests\Municipality;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $officeId = $this->user()?->government_office_id;

        return [
            'action' => ['required', Rule::in(['approve', 'reschedule', 'cancel'])],
            'time_slot_id' => [
                Rule::requiredIf($this->input('action') === 'reschedule'),
                'nullable',
                Rule::exists('time_slots', 'id')->where(function ($query) use ($officeId) {
                    $query->where('government_office_id', $officeId ?? 0);
                }),
            ],
            'municipality_notes' => ['nullable', 'string'],
        ];
    }
}
