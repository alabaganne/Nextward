<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['user_id', 'gateway', 'pack', 'credits', 'amount', 'currency', 'status', 'provider_ref', 'paid_at', 'payload'];

    protected $casts = ['paid_at' => 'datetime', 'payload' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
