<?php

namespace App\Http\Requests\VerificationRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVerificationFormRequest extends FormRequest
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

            'id'=>'integer|required|exists:verification_requests,id',
            'national_id' => [
                'sometimes',
                'string',
                'regex:/^[0-9]{11}$/',
                Rule::unique('users', 'national_id')->ignore($this->user()),
            ],

            'images_to_delete'=>'sometimes|array',
            'images_to_delete.*'=>'nullable|integer|exists:verification_images,id',
            'images_to_upload'=>'nullable|array',
            'images_to_upload.*'=>'image|mimes:jpeg,jpg,png,webp|max:4096',

        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Verification request ID is required',
            'id.exists' => 'Verification request not found',
            'national_id.regex' => 'National ID must be exactly 11 digits',
            'national_id.unique' => 'This National ID is already registered',
            'images_to_delete.array' => 'Images to delete must be a list',
            'images_to_delete.*.exists' => 'One of the selected images does not exist',
            'images_to_upload.array' => 'Images must be a list of files',
            'images_to_upload.*.image' => 'Each image must be a valid image file',
            'images_to_upload.*.mimes' => 'Unsupported image format. Allowed: JPG, JPEG, PNG, WEBP',
            'images_to_upload.*.max' => 'Image size must not exceed 4MB',
        ];
    }
}
