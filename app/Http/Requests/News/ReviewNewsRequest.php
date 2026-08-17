<?php

namespace App\Http\Requests\News;

use Illuminate\Foundation\Http\FormRequest;

class ReviewNewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|nullable|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be approve or reject',
            'rejection_reason.required_if' => 'Rejection reason is required when rejecting',
        ];
    }
}