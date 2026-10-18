<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];

    public function opportunities() {
        return $this->morphedByMany(Opportunity::class, 'skillable');
    }

    public function candidates() {
        return $this->morphedByMany(Candidate::class, 'skillable');
    }
}
