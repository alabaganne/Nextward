<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A profile read from a resume with `opportunities:profile`; imports add listings it wants.
class SearchProfile extends Model
{
    protected $fillable = ['name', 'data'];

    protected $casts = ['data' => 'array'];
}
