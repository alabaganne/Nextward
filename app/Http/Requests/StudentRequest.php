<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('country_code') && is_string($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper(trim($this->input('country_code')))]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if($this->student) {
            $user_id = $this->student->user->id;
        } else {
            $user_id = "";
        }
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user_id,
            'phone_number' => 'nullable|string|max:255|unique:users,phone_number,' . $user_id,
            'field_id' => 'nullable|exists:fields,id',
            'city_id' => 'nullable|exists:cities,id',
            'about' => 'nullable|string|max:20000',
            'career_stage' => 'sometimes|required|in:student,graduate,professional',
            'headline' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0|max:80',
            'country_code' => 'nullable|string|size:2|regex:/^[A-Z]{2}$/',
            'location' => 'nullable|string|max:255',
        ];
    }
}
