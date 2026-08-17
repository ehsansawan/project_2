<?php

namespace App\Http\Requests\Project;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
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
            'id' => ['required', 'integer', 'exists:projects,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'media' => ['nullable', 'array'],
            'media.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,mp4,mov',
                'max:20480',
            ],
            'requires_volunteers' => ['nullable', 'boolean'],
            'requires_donations' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Project ID is required',
            'id.exists' => 'Project not found',
            'name.max' => 'Project title may not be greater than 255 characters',
            'media.*.file' => 'Each media must be a valid uploaded file',
            'media.*.mimes' => 'Unsupported file format. Allowed: JPG, PNG, WEBP, MP4, MOV',
            'media.*.max' => 'File size must not exceed 20MB',
            'latitude.between' => 'Latitude must be between -90 and 90',
            'longitude.between' => 'Longitude must be between -180 and 180',
        ];
    }
}