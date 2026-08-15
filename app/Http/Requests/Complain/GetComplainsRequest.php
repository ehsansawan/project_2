<?php

namespace App\Http\Requests\Complain;

use Illuminate\Foundation\Http\FormRequest;

class GetComplainsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
           'type' => 'nullable|in:individual,collective,emergency',
            'category_id' => 'nullable|exists:complain_categories,id',
            'sort' => 'nullable|in:priority,newest,oldest',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Invalid complaint type',
            'category_id.exists' => 'Invalid category selected',
            'sort.in' => 'Invalid sort option',
            'page.integer' => 'Page must be a number',
            'page.min' => 'Page must be at least 1',
            'page_size.integer' => 'Page size must be a number',
            'page_size.min' => 'Page size must be at least 1',
            'page_size.max' => 'Page size may not exceed 100',
        ];
    }
}