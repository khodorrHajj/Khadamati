<?php

namespace App\Http\Requests\Admin;

use App\Models\ServiceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ServiceRequest::statuses())],
            'official_response' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:5120'],
            'official_response_document_type' => ['nullable', 'string', 'max:255'],
            'admin_internal_note' => ['nullable', 'string'],
            'workflow_state' => ['nullable', Rule::in(ServiceRequest::workflowStates())],
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $serviceRequest = $this->route('serviceRequest');
                    $officeId = $serviceRequest?->service?->government_office_id ?? 0;

                    $query->where('government_office_id', $officeId)
                        ->whereExists(function ($subQuery) {
                            $subQuery->selectRaw('1')
                                ->from('roles')
                                ->whereColumn('roles.id', 'users.role_id')
                                ->where('roles.role', 'municipality');
                        });
                }),
            ],
            'generate_official_response_pdf' => ['nullable', 'boolean'],
            'official_response_summary' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
