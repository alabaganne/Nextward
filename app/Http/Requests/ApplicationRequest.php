<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->isCandidate() || auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'cover_letter' => 'required|string|min:200|max:30000',
            'message' => 'nullable|string|min:100|max:10000',
            'attachment_files' => 'nullable|array|max:10',
            'attachment_files.*' => 'file|mimes:pdf,doc,docx,txt,rtf|max:10240',
        ];
    }
}
