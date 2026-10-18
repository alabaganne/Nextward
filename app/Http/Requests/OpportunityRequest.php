<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpportunityRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->isCompany() || $this->user()?->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];
        foreach (['country_code', 'pay_currency'] as $field) {
            if ($this->filled($field) && is_string($this->input($field))) {
                $normalized[$field] = strtoupper(trim($this->input($field)));
            }
        }
        foreach (['eligible_countries', 'excluded_countries'] as $field) {
            if (is_array($this->input($field))) {
                $normalized[$field] = array_map(fn ($country) => is_string($country) ? strtoupper(trim($country)) : $country, $this->input($field));
            }
        }
        $this->merge($normalized);
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:100000',
            'kind' => 'sometimes|required|in:internship,job',
            'field_id' => 'nullable|integer|exists:fields,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'country_code' => 'nullable|string|regex:/^[A-Z]{2}$/',
            'location' => 'nullable|string|max:255',
            'closing_at' => 'nullable|date_format:Y-m-d',
            'work_type' => 'nullable|in:remote,hybrid,onsite',
            'duration_weeks' => 'nullable|integer|min:1|max:104',
            'term' => 'nullable|string|max:255',
            'program_type' => 'nullable|in:pfe,summer,apprenticeship,graduate,other',
            'start_date' => 'nullable|date_format:Y-m-d',
            'employment_types' => 'nullable|array|max:7',
            'employment_types.*' => ['string', 'distinct', Rule::in(['FULL_TIME', 'PART_TIME', 'CONTRACTOR', 'TEMPORARY', 'INTERN', 'VOLUNTEER', 'OTHER'])],
            'seniority' => 'nullable|in:entry,graduate,mid,senior,lead,executive',
            'language' => 'nullable|string|regex:/^[a-z]{2}(-[A-Z]{2})?$/',
            'pay_amount' => 'nullable|integer|min:0|max:999999999',
            'pay_min' => 'nullable|integer|min:0|max:9999999999',
            'pay_max' => 'nullable|integer|min:0|max:9999999999',
            'pay_currency' => 'nullable|required_with:pay_min,pay_max|string|regex:/^[A-Z]{3}$/',
            'pay_unit' => 'nullable|required_with:pay_min,pay_max|in:hour,day,week,month,year',
            'compensation_status' => 'nullable|in:paid,unpaid,unspecified',
            'remote_scope' => 'nullable|string|max:255',
            'remote_eligibility' => 'nullable|in:eligible,restricted,unknown',
            'relocation' => 'nullable|in:yes,no,unknown',
            'visa_sponsorship' => 'nullable|in:yes,no,unknown',
            'eligible_countries' => 'nullable|array|max:250',
            'eligible_countries.*' => 'string|distinct|regex:/^[A-Z]{2}$/',
            'excluded_countries' => 'nullable|array|max:250',
            'excluded_countries.*' => 'string|distinct|regex:/^[A-Z]{2}$/',
            'status' => 'sometimes|required|in:open,closed',
            'publication_status' => 'sometimes|required|in:published,draft',
            'attachment_files' => 'nullable|array|max:10',
            'attachment_files.*' => 'file|mimes:pdf,doc,docx,txt|max:10240',
            'remove_attachments' => 'nullable|boolean',
            'skills' => 'nullable|array|max:12',
            'skills.*' => 'integer|distinct|exists:skills,id',
        ];
    }

    public function messages()
    {
        return [
            'skills.max' => 'Pick up to 12 skills.',
            'skills.*.exists' => 'One of the chosen skills no longer exists.',
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            if ($this->filled('pay_min') && $this->filled('pay_max') && (float) $this->input('pay_max') < (float) $this->input('pay_min')) {
                $validator->errors()->add('pay_max', 'Maximum compensation must be at least the minimum.');
            }
            if ($this->input('compensation_status') === 'unpaid' && ((float) $this->input('pay_min', 0) > 0 || (float) $this->input('pay_max', 0) > 0)) {
                $validator->errors()->add('compensation_status', 'An unpaid opportunity cannot advertise positive compensation.');
            }
            $allowed = $this->input('eligible_countries', []);
            $excluded = $this->input('excluded_countries', []);
            if (is_array($allowed) && is_array($excluded) && array_intersect(array_filter($allowed, 'is_string'), array_filter($excluded, 'is_string'))) {
                $validator->errors()->add('excluded_countries', 'A country cannot be both eligible and excluded.');
            }
        }];
    }
}
