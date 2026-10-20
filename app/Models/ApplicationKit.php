<?php

namespace App\Models;

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
}
