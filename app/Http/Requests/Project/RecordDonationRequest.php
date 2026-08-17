<?php

namespace App\Http\Requests\Project;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecordDonationRequest extends FormRequest
{
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
            'id' => ['required', 'integer', 'exists:projects,id'],
            'donor_user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Project ID is required',
            'id.exists' => 'Project not found',
            'donor_user_id.required' => 'Please specify the donor',
            'donor_user_id.exists' => 'Donor not found',
            'amount.required' => 'Please specify the donation amount',
            'amount.min' => 'Donation amount must be greater than zero',
        ];
    }
}
