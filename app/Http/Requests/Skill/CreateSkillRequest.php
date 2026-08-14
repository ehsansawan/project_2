<?php

namespace App\Http\Requests\Skill;

use App\Enums\SkillType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateSkillRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'type'=>['required',new Enum(SkillType::class)]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Skill name is required',
            'name.max' => 'Skill name may not be greater than 255 characters',
            'type.required' => 'Skill type is required',
            'type.enum' => 'The selected skill type is invalid',
        ];
    }
}
