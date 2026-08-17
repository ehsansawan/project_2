<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class GetNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:news,announcement,emergency_alert',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ];
    }
}