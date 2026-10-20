<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'amount', 'reason', 'application_kit_id', 'resume_id', 'payment_id', 'note'];
}
