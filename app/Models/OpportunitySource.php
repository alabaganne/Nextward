<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunitySource extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'payload' => 'array', 'normalized_data' => 'array', 'ai_review' => 'array', 'first_seen_at' => 'datetime', 'last_discovered_at' => 'datetime',
        'last_verified_at' => 'datetime', 'missing_since' => 'datetime',
    ];
    public function importSource() { return $this->belongsTo(ImportSource::class); }
    public function internship() { return $this->belongsTo(Internship::class); }
}
