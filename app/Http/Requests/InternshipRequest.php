<?php

namespace App\Http\Requests;
use Illuminate\Support\Str;

use Illuminate\Foundation\Http\FormRequest;

class InternshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->isCompany() ||
            auth()->user()->isAdmin(); // can be used when the admin is adding an internship for a company
    }

    public function messages() {
        return [
            'field_id.required' => 'You must select a field of studies.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required',
            'description' => 'required',
            'field_id' => 'required',
            'closing_at' => 'required|date',
            'work_type' => 'nullable|in:remote,hybrid,onsite',
            'duration_weeks' => 'nullable|integer|min:1|max:104',
            'term' => 'nullable|string|max:255',
            'pay_amount' => 'nullable|integer|min:0',
            'pay_unit' => 'nullable|in:hour,month',
            'attachments' => 'nullable',
            'attachment_files' => 'nullable|array|max:10',
            'attachment_files.*' => 'file|max:10240',
        ];
    }
}
