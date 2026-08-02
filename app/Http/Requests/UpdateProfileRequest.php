<?php

namespace App\Http\Requests;

use App\Enums\AccountStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $canEditIdentity = auth()->user()->account_status === AccountStatus::Visitor->value;

        return [
            'birth_date' => [Rule::when($canEditIdentity, 'date', 'prohibited')],
            'first_name' => [Rule::when($canEditIdentity, 'string', 'prohibited'), 'max:255'],
            'last_name' => [Rule::when($canEditIdentity, 'string', 'prohibited'), 'max:255'],
            'image'=>'nullable|image|mimes:jpeg,jpg,png,webp|max:4096'
        ];
    }
}
