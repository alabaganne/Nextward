<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkillRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function messages()
    {
        return [
            'name.unique' => 'A skill with that name already exists.',
        ];
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:80', Rule::unique('skills', 'name')->ignore($this->skill?->id)],
        ];
    }
}
