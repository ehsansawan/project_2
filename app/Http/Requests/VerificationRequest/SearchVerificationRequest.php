<?php

namespace App\Http\Requests\VerificationRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'national_id' => 'required|string|digits:11',
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.required' => 'National ID is required',
            'national_id.digits' => 'National ID must be exactly 11 digits',
        ];
    }
}
