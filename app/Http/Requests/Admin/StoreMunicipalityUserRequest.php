<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMunicipalityUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'government_office_id' => ['required', 'exists:government_offices,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^(?:0(?:1|3|5)\d{6}|(?:70|71|76|78|79|81)\d{6}|\+961(?:1|3|5|70|71|76|78|79|81)\d{6})$/',
                'unique:users,phone',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'job_title' => ['nullable', 'string', Rule::in(\App\Models\User::MUNICIPALITY_POSITIONS)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid Lebanese phone number.',
        ];
    }
}
