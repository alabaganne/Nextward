<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A listing's title and description in English or French, written by AI from the original.
class OpportunityTranslation extends Model
{
    protected $fillable = ['opportunity_id', 'locale', 'title', 'description', 'slug', 'source_hash', 'model'];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }
}
