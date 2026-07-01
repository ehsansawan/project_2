<?php

namespace App\Http\Requests\Complain;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateComplainRequest extends FormRequest
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
            'type' => 'required|in:individual,collective,emergency',
            'category_id' => 'required|exists:complain_categories,id',
            'title' => 'required|string|min:5|max:200',
            'description' => 'required_unless:type,emergency|nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'media' => 'nullable|array',
            'media.*' => [
                'file',
                'mimes:jpg,jpeg,png,mp4,mov',
                'max:20480',
            ],
       ];
    }

      public function messages(): array
        {
            return [
                'type.required' => 'Please select complaint type',
                'category_id.required' => 'Please select complaint category',
                'category_id.exists' => 'The selected category is invalid',
                'title.required' => 'Invalid complaint title',
                'title.min' => 'Title must be at least 5 characters',
                'title.max' => 'Title may not be greater than 200 characters',
                'description.required_unless' => 'Please enter complaint description',
                'latitude.required' => 'Please select location',
                'longitude.required' => 'Please select location',
                'media.array' => 'Media must be a list of files',
                'media.*.file' => 'Each media must be a valid uploaded file',
                'media.*.mimes' => 'Unsupported file format. Allowed: JPG, PNG, JPEG, MP4, MOV',
                'media.*.max' => 'File size must not exceed 20MB',
            ];
        }
}
