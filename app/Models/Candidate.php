<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'about',
        'field_id',
        'city_id',
        'headline',
        'experience_years',
        'country_code',
        'location',
        'languages',
        'preferences',
        'website',
        'resume_path',
        'resume_name',
        'experience',
        'education',
        'projects',
    ];

    protected $hidden = ['resume_path'];

    protected $casts = [
        'preferences' => 'array',
        'languages' => 'array',
        'experience' => 'array',
        'education' => 'array',
        'projects' => 'array',
        'experience_years' => 'integer',
        'public_reviewed_at' => 'datetime',
    ];

    public function scopePubliclyListed($query)
    {
        return $query->where('public_status', 'approved')->whereHas('user');
    }

    public function isPubliclyListed(): bool
    {
        return $this->public_status === 'approved';
    }

    public function publicUrl(string $locale = 'en'): string
    {
        return rtrim(config('app.url'), '/').($locale === 'fr' ? '/fr' : '').'/candidates/'.$this->id.'-'.(Str::slug($this->user?->name ?? '') ?: 'candidate');
    }

    public function user() {
        return $this->morphOne(User::class, 'userable');
    }

    public function field() {
        return $this->belongsTo(Field::class);
    }

    public function city() {
        return $this->belongsTo(City::class);
    }

    public function skills() {
        return $this->morphToMany(Skill::class, 'skillable');
    }

    public function applications() { // internships that the user has applied to
        return $this->belongsToMany(Opportunity::class, 'applications')
            ->withPivot(['cover_letter', 'message', 'attachments', 'status', 'channel', 'applied_at'])
            ->withTimestamps();
    }

    public function likes() {
        return $this->belongsToMany(Opportunity::class, 'likes');
    }

    public function openedOpportunities() { // external links the candidate clicked
        return $this->belongsToMany(Opportunity::class, 'opportunity_views')->withPivot('viewed_at');
    }
}
