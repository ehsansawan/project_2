<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:news,announcement',
            'target_audience' => 'nullable|in:all,specific_skills,specific_area',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'description.required' => 'Description is required',
            'type.required' => 'Content type is required',
            'type.in' => 'Type must be news or announcement',
            'image.mimes' => 'Image must be jpg, jpeg, png or webp',
            'latitude.required_with' => 'Latitude is required when longitude is provided',
            'longitude.required_with' => 'Longitude is required when latitude is provided',
        ];
    }
}