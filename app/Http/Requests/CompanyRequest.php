<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
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
            $user_id = $this->company->user->id;
        } else {
            $user_id = "";
        }
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user_id,
            'phone_number' => 'nullable|unique:users,phone_number,' .$user_id,
            'city_id' => 'required|exists:cities,id',
            'website' => 'nullable|url',
            'about' => 'nullable'
        ];
    }
}
