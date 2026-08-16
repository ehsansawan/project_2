<?php

namespace App\Http\Requests\Certifications;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateRequest extends FormRequest
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
            'user_skill_id' => [
                'required',
                'integer',
                Rule::exists('user_skills', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id());
                }),
            ],
            'file_path' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp,pdf',
                'max:4096',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_skill_id.required' => 'Skill ID is required.',
            'user_skill_id.exists' => 'The selected skill is invalid.',
            'file_path.required' => 'Certificate file is required.',
            'file_path.mimes' => 'The certificate must be a file of type: jpeg, jpg, png, webp, pdf.',
        ];
    }
}
