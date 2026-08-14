<?php

namespace App\Http\Requests\Complain;

use Illuminate\Foundation\Http\FormRequest;

class ReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject',
            'decision_reason' => 'required|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'decision_reason.required' => 'Decision reason is required (report approval deletes the complaint)',
        ];
    }
}