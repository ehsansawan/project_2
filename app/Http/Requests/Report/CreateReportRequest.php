<?php

namespace App\Http\Requests\Report;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'complain_id' =>[
                'required',
                Rule::exists('complains','id')->where('status','published')
            ],
            'type_id' =>[
                'required',
                'exists:report_types,id'
            ],
            'description' => [
                'required',
                'string',
                'min:10','
                max:1000'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'complain_id.required' => 'Complain ID is required',
            'complain_id.exists' => 'The selected complain is invalid or not published',
            'type_id.required' => 'Please select report reason',
            'type_id.exists' => 'The selected report reason is invalid',
            'description.required' => 'Please enter a report description',
            'description.min' => 'Description must be at least 10 characters',
            'description.max' => 'Description may not be greater than 1000 characters',
        ];
    }
}
