<?php

namespace App\Http\Requests\Queue;

use Illuminate\Foundation\Http\FormRequest;

class JoinQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'user_name' => 'required|string|min:2|max:50',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'Service ID is required',
            'service_id.exists' => 'Invalid service selected',
            'user_name.required' => 'Please enter your name',
            'user_name.min' => 'Name must be at least 2 characters',
            'user_name.max' => 'Name may not be greater than 50 characters',
            'latitude.required' => 'Location is required',
            'longitude.required' => 'Location is required',
        ];
    }
}