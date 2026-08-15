<?php

namespace App\Http\Requests\Complain;

use Illuminate\Foundation\Http\FormRequest;

class GetAdminComplainsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|in:individual,collective,emergency',
            'status' => 'nullable|in:under_review,published,rejected,in_progress,closed',
            'category_id' => 'nullable|exists:complain_categories,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => 'nullable|in:priority,newest,oldest',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Invalid complaint type',
            'status.in' => 'Invalid complaint status',
            'category_id.exists' => 'Invalid category selected',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
            'sort.in' => 'Invalid sort option',
            'page.min' => 'Page must be at least 1',
            'page_size.min' => 'Page size must be at least 1',
            'page_size.max' => 'Page size may not exceed 100',
        ];
    }
}