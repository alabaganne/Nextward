<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'website',
        'about',
        'city_id',
        'name',
        'domain',
        'logo_url',
        'country_code',
        'location',
    ];

    public function user() {
        return $this->morphOne(User::class, 'userable');
    }

    public function internships() {
        return $this->hasMany(Internship::class);
    }

    public function city() {
        return $this->belongsTo(City::class);
    }

	public function applications() {
		return $this->hasMany(Application::class)->where('channel', 'internal');
	}

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?: $this->name ?: 'Employer not specified';
    }

    public function getLogoAttribute(): ?string
    {
        return $this->user?->image ?: $this->logo_url;
    }

    public function getPublicUrlAttribute(): string
    {
        return $this->publicUrl();
    }

    public function publicUrl(string $locale = 'en'): string
    {
        return rtrim(config('app.url'), '/').($locale === 'fr' ? '/fr' : '').'/companies/'.$this->id.'-'.(Str::slug($this->display_name) ?: 'company');
    }

    public function hasPublicName(): bool
    {
        return (bool) ($this->user?->name ?: $this->name);
    }

    // Worth indexing: open roles, or enough about text to stand alone.
    public function isIndexable(int $openRoles): bool
    {
        return !$this->claimed_by_company_id && $this->hasPublicName() && ($openRoles > 0 || mb_strlen(trim((string) $this->about)) >= 300);
    }

    public function scopePublicIndexable($query)
    {
        return $query->whereNull('claimed_by_company_id')->with('user')
            ->where(fn ($query) => $query->whereNotNull('name')->where('name', '!=', '')->orWhereHas('user'))
            ->where(fn ($query) => $query->whereHas('internships', fn ($query) => $query->available())->orWhereRaw('LENGTH(about) >= 300'));
    }
}
