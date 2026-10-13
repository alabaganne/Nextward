<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
        return true;
    }

    public function messages() {
        return [
            'name.required' => 'The company name is required',
            'city_id.required' => 'The city field is required',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if($this->company) {
            $user_id = $this->company->user?->id ?? '';
        } else {
            $user_id = "";
        }
        return [
            'name' => 'required|string|max:255',
            'email' => ($this->company && !$this->company->user ? 'nullable' : 'required').'|email|unique:users,email,' . $user_id,
            'phone_number' => 'nullable|string|max:255|unique:users,phone_number,' .$user_id,
            'city_id' => 'nullable|exists:cities,id',
            'country_code' => 'nullable|string|size:2|regex:/^[A-Z]{2}$/',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url:http,https|max:2048',
            'about' => 'nullable|string|max:20000',
        ];
    }
}
