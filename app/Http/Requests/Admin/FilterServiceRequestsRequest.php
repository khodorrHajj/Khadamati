<?php

namespace App\Http\Requests\Admin;

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
        return [
            'status' => ['nullable', Rule::in(ServiceRequest::statuses())],
            'workflow_state' => ['nullable', Rule::in(ServiceRequest::workflowStates())],
            'assignment_scope' => ['nullable', Rule::in(['assigned', 'unassigned', 'escalated'])],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'municipality' => ['nullable', 'integer', 'exists:municipalities,id'],
            'office' => ['nullable', 'integer', 'exists:government_offices,id'],
            'service' => ['nullable', 'integer', 'exists:services,id'],
            'category' => ['nullable', 'integer', 'exists:service_categories,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:255'],
            'tracking_code' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'status' => $validated['status'] ?? null,
            'workflow_state' => $validated['workflow_state'] ?? null,
            'assignment_scope' => $validated['assignment_scope'] ?? null,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
            'municipality' => $validated['municipality'] ?? null,
            'office' => $validated['office'] ?? null,
            'service' => $validated['service'] ?? null,
            'category' => $validated['category'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'search' => $validated['search'] ?? null,
            'tracking_code' => $validated['tracking_code'] ?? null,
        ];
    }
}
