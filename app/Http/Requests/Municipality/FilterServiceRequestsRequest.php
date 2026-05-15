<?php

namespace App\Http\Requests\Municipality;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterServiceRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $officeId = $this->user()?->government_office_id;

        return [
            'status' => ['nullable', Rule::in(ServiceRequest::statuses())],
            'handoff_scope' => ['nullable', Rule::in(['all', 'assigned_to_me', 'unassigned', 'awaiting_admin', 'awaiting_municipality'])],
            'service' => [
                'nullable',
                Rule::exists('services', 'id')->where(function ($query) use ($officeId) {
                    $query->where('government_office_id', $officeId ?? 0);
                }),
            ],
            'category' => [
                'nullable',
                Rule::exists('service_categories', 'id')->where(function ($query) use ($officeId) {
                    $query->where('government_office_id', $officeId ?? 0);
                }),
            ],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'status' => $validated['status'] ?? null,
            'handoff_scope' => $validated['handoff_scope'] ?? 'all',
            'service' => $validated['service'] ?? null,
            'category' => $validated['category'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'search' => $validated['search'] ?? null,
        ];
    }
}
