<?php

namespace App\Models;

use App\Importing\JobKey;
use Illuminate\Database\Eloquent\Model;

// Another name a company goes by, such as "Tether Operations Limited" for Tether. Both are saved as JobKey::name() keys.
class CompanyAlias extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn () => JobKey::forget());
        static::deleted(fn () => JobKey::forget());
    }

    public function company() { return $this->belongsTo(Company::class); }

    public static function remember(string $alias, string $name, ?int $companyId = null): void
    {
        $alias = JobKey::name($alias); $name = JobKey::name($name);
        if ($alias === '' || $name === '' || $alias === $name) { return; }
        self::updateOrCreate(['alias' => $alias], ['name' => $name, 'company_id' => $companyId]);
    }
}
