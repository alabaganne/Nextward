<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A job the daily run looked at and left out, with the reason.
class LeadSkip extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['run_date' => 'date'];
}
