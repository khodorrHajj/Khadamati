<?php

namespace App\Http\Requests\Citizen;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ServiceRequest|null $serviceRequest */
        $serviceRequest = $this->route('serviceRequest');
        $officeId = $serviceRequest?->service?->government_office_id;

        return [
            'time_slot_id' => [
                'required',
                Rule::exists('time_slots', 'id')->where(function ($query) use ($officeId) {
                    $query->where('government_office_id', $officeId ?? 0);
                }),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
