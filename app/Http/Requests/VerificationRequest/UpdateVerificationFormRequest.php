<?php

namespace App\Http\Requests\VerificationRequest;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'national_id'=>'numeric|digits:11',
            'images_to_delete'=>'sometimes|array',
            'images_to_delete.*'=>'nullable|integer|exists:verification_images,id',
            'images_to_upload'=>'nullable|array',
            'images_to_upload.*'=>'image|mimes:jpeg,jpg,png,webp|max:4096',

        ];
    }
}
