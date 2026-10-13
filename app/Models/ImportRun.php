<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRun extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['complete' => 'boolean', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    public function importSource() { return $this->belongsTo(ImportSource::class); }
}
