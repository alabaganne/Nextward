<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['filters' => 'array', 'last_sent_at' => 'datetime', 'subscribed_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
}
