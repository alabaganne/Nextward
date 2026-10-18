<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationKit extends Model
{
    protected $fillable = [
        'student_id', 'internship_id', 'locale', 'status', 'cv', 'cover_letter', 'error',
        'model', 'input_tokens', 'output_tokens', 'generations', 'credits_used',
    ];

    protected $casts = ['cv' => 'array'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function internship()
    {
        return $this->belongsTo(Internship::class);
    }
}
