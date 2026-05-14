<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:5120', 'required_without:body'],
        ];
    }
}
