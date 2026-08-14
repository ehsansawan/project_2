<?php

namespace App\Http\Requests\Complain;

use Illuminate\Foundation\Http\FormRequest;

class ReviewComplainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'decision_reason' => 'required_if:action,reject|nullable|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be approve or reject',
            'decision_reason.required_if' => 'Decision reason is required when rejecting a complaint',
            'decision_reason.min' => 'Decision reason must be at least 5 characters',
        ];
    }
}