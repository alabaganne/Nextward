<?php

namespace App\Models;

use App\Importing\JobKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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

    protected $casts = ['logo_fetched_at' => 'datetime', 'verified_at' => 'datetime', 'researched_at' => 'datetime', 'verification' => 'array'];

    protected static function booted(): void
    {
        // A new outside logo is downloaded again by companies:logos.
        static::saving(function (Company $company) {
            if ($company->isDirty('logo_url')) {
                $company->logo_fetched_at = null;
            }
            if ($company->isDirty('name')) {
                $company->name_key = JobKey::company((string) $company->name) ?: null;
            }
        });
        static::deleted(function (Company $company) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
        });
    }

    public function user() {
        return $this->morphOne(User::class, 'userable');
    }

    public function opportunities() {
        return $this->hasMany(Opportunity::class);
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

    // Full public URL of a file on the public disk; outside URLs are never sent to the browser.
    public function getLogoAttribute(): ?string
    {
        $image = $this->user?->image;
        if (! $image || preg_match('#^https?://#i', $image)) {
            $image = $this->logo_path;
        }

        return $image ? url(Storage::disk('public')->url($image)) : null;
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

    // Companies that can have a public page: not merged into another one, and with a name.
    public function scopeListed($query)
    {
        return $query->whereNull('claimed_by_company_id')
            ->where(fn ($query) => $query->whereNotNull('name')->where('name', '!=', '')->orWhereHas('user'));
    }

    public function scopePublicIndexable($query)
    {
        return $query->listed()->with('user')
            ->where(fn ($query) => $query->whereHas('opportunities', fn ($query) => $query->available())->orWhereRaw('LENGTH(about) >= 300'));
    }
}
