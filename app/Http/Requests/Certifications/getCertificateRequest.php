<?php

namespace App\Http\Requests\Certifications;

use App\Enums\CertificateStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class getCertificateRequest extends FormRequest
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
            //
            'status'=>['sometimes','array'],
            'status.*'=>[Rule::enum(CertificateStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.array' => 'Status must be a list',
            'status.*.enum' => 'One of the selected statuses is invalid',
        ];
    }
}
