<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApplicationRequest extends FormRequest
{
    public const MAX_FILES = 10;

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
        $application = $this->route('application');
        $candidateId = $application instanceof Application ? $application->candidate_id : $this->user()->userable_id;
        $opportunityId = $application instanceof Application ? $application->opportunity_id : $this->route('opportunity')?->id;

        return [
            'cover_letter' => 'required|string|min:200|max:30000',
            'message' => 'nullable|string|min:100|max:10000',
            'attachment_files' => 'nullable|array|max:'.self::MAX_FILES,
            'attachment_files.*' => 'file|mimes:pdf,doc,docx,txt,rtf|max:10240',
            'resume_id' => ['nullable', 'integer', 'prohibits:kit_id', Rule::exists('resumes', 'id')->where('candidate_id', $candidateId)->where('status', 'ready')->whereNotNull('path')],
            'kit_id' => ['nullable', 'integer', Rule::exists('application_kits', 'id')->where('candidate_id', $candidateId)->where('opportunity_id', $opportunityId)->whereNotNull('cv')],
            'remove_resume' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'resume_id.exists' => 'Pick one of your saved resumes.',
            'kit_id.exists' => 'Pick a tailored CV made for this listing.',
        ];
    }

    // The resume counts toward the 10 files.
    public function after(): array
    {
        return [function (Validator $validator) {
            $application = $this->route('application');
            $kept = collect($application instanceof Application ? $application->attachments ?: [] : []);
            $hasResume = $this->filled('resume_id') || $this->filled('kit_id')
                || (! $this->boolean('remove_resume') && $kept->contains(fn ($file) => $file['resume'] ?? false));
            $total = $kept->reject(fn ($file) => $file['resume'] ?? false)->count() + count($this->file('attachment_files', [])) + ($hasResume ? 1 : 0);
            if ($total > self::MAX_FILES) {
                $validator->errors()->add('attachment_files', 'You can send up to '.self::MAX_FILES.' files in all, your resume included.');
            }
        }];
    }
}
