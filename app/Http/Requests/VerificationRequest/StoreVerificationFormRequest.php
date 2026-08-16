<?php

namespace App\Http\Requests\VerificationRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVerificationFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'national_id' => ['required',
                'string',
                'regex:/^[0-9]{11}$/',
                'unique:users,national_id'],
            'images' => 'required|array|size:2',
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:4096',

        ];
    }

    public function messages(): array
    {
        return [
            'national_id.required' => 'National ID is required',
            'national_id.regex' => 'National ID must be exactly 11 digits',
            'national_id.unique' => 'This National ID is already registered',
            'images.required' => 'Please upload your ID image',
            'images.array' => 'Images must be a list of files',
            'images.size' => 'Exactly 1 image is required',
            'images.*.image' => 'Each image must be a valid image file',
            'images.*.mimes' => 'Unsupported image format. Allowed: JPG, JPEG, PNG, WEBP',
            'images.*.max' => 'Image size must not exceed 4MB',
        ];
    }
}
