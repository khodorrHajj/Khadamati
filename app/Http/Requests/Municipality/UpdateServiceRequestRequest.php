<?php

namespace App\Http\Requests\Municipality;

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
            'notes' => [
                Rule::requiredIf(fn () => $this->input('status') === ServiceRequest::STATUS_MISSING_DOCUMENTS),
                'nullable',
                'string',
            ],
            'escalate_to_admin' => ['nullable', 'boolean'],
            'escalation_reason' => [
                Rule::requiredIf(fn () => $this->boolean('escalate_to_admin')),
                'nullable',
                'string',
                'max:5000',
            ],
            'missing_document_items' => ['nullable', 'array'],
            'missing_document_items.*' => ['string', 'max:255'],
            'official_response' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:5120'],
            'official_response_document_type' => ['nullable', 'string', 'max:255'],
            'generate_official_response_pdf' => ['nullable', 'boolean'],
            'official_response_summary' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
