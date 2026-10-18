<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A listing's title and description in English or French, written by AI from the original.
class InternshipTranslation extends Model
{
    protected $fillable = ['internship_id', 'locale', 'title', 'description', 'slug', 'source_hash', 'model'];

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }
}
