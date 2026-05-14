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
            'notes' => ['nullable', 'string'],
            'official_response' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }
}
