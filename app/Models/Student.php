<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'about',
        'field_id',
        'city_id',
        'career_stage',
        'headline',
        'experience_years',
        'country_code',
        'location',
        'languages',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
        'languages' => 'array',
        'experience_years' => 'integer',
    ];

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
        return $this->belongsToMany(Internship::class, 'applications')
            ->withPivot(['cover_letter', 'message', 'attachments', 'status', 'channel', 'applied_at'])
            ->withTimestamps();
    }

    public function likes() {
        return $this->belongsToMany(Internship::class, 'likes');
    }

    public function openedOpportunities() { // external links the candidate clicked
        return $this->belongsToMany(Internship::class, 'opportunity_views')->withPivot('viewed_at');
    }
}
