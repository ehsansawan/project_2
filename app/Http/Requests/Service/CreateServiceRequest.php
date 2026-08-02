<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class CreateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
            'prefix' => 'required|string|size:1',
            'estimated_time_minutes' => 'required|integer|min:1|max:120',
            'status' => 'nullable|in:active,paused,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Service name is required',
            'name.min' => 'Service name must be at least 3 characters',
            'prefix.required' => 'Service prefix is required',
            'prefix.size' => 'Prefix must be exactly 1 character',
            'estimated_time_minutes.required' => 'Estimated time is required',
            'estimated_time_minutes.min' => 'Estimated time must be at least 1 minute',
        ];
    }
}