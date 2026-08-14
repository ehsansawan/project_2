<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|min:3|max:255',
            'prefix' => 'sometimes|string|size:1',
            'estimated_time_minutes' => 'sometimes|integer|min:1|max:120',
            'status' => 'sometimes|in:active,paused,closed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Service name must be at least 3 characters',
            'name.max' => 'Service name may not be greater than 255 characters',
            'prefix.size' => 'Prefix must be exactly 1 character',
            'estimated_time_minutes.min' => 'Estimated time must be at least 1 minute',
            'estimated_time_minutes.max' => 'Estimated time may not be greater than 120 minutes',
            'status.in' => 'Status must be one of: active, paused, closed',
        ];
    }
}