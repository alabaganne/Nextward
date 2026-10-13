<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    // The notifications table uses uuid primary keys (Laravel database notifications).
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'data',
        'read_at',
    ];

	protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];
}
