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
}