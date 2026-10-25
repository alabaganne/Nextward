<?php

namespace App\Models;

use App\Importing\JobKey;
use Illuminate\Database\Eloquent\Model;

// Someone who works at a company in the owner's job leads, to reach out to.
class LeadContact extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['contacted_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (LeadContact $contact) {
            $contact->company_key = JobKey::company($contact->company_name);
        });
    }
}
