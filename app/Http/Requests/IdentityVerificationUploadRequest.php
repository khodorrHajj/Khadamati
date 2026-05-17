<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IdentityVerificationUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_image_front' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'id_image_back' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
