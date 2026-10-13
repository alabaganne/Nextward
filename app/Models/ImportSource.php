<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportSource extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'allowed_domains' => 'array', 'settings' => 'array', 'enabled' => 'boolean',
        'last_run_at' => 'datetime', 'last_complete_at' => 'datetime', 'next_run_at' => 'datetime',
    ];

    public function items() { return $this->hasMany(OpportunitySource::class); }
    public function runs() { return $this->hasMany(ImportRun::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function isApproved(): bool { return $this->enabled && $this->permission_status === 'approved'; }
    public function freshnessHours(): int { return max((int) config('importer.freshness_hours', 48), (int) $this->cadence_hours * 2); }
}
