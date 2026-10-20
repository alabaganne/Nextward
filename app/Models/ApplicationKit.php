<?php

namespace App\Models;

use App\Support\CvTemplates;
use Illuminate\Database\Eloquent\Model;

class ApplicationKit extends Model
{
    protected $fillable = [
        'candidate_id', 'opportunity_id', 'locale', 'status', 'cv', 'cover_letter', 'style', 'error',
        'model', 'input_tokens', 'output_tokens', 'generations', 'credits_used',
    ];

    protected $casts = ['cv' => 'array', 'style' => 'array'];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    // "Ala_Baganne_CV_Canonical.pdf"
    public function fileName(string $kind): string
    {
        return CvTemplates::fileName($this->cv['name'] ?? null, CvTemplates::labels($this->locale)[$kind].' '.$this->opportunity?->company?->display_name);
    }
}
