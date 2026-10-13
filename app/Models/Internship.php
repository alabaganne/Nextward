<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'company_id',
        'field_id',
		'city_id',
        'attachments',
        'closing_at',
        'work_type',
        'duration_weeks',
        'term',
        'pay_amount',
        'pay_unit',
        'status',
    ];

    protected $casts = [
        'attachments' => 'array',
        'closing_at' => 'datetime:Y-m-d'
    ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function field() {
        return $this->belongsTo(Field::class);
    }

	public function city() {
		return $this->belongsTo(City::class);
	}

    public function skills() {
        return $this->morphToMany(Skill::class, 'skillable');
    }

    public function likes() { // students who saved this internship
        return $this->belongsToMany(Student::class, 'likes');
    }

    public function applications() { // students who applied for this internship
        return $this->belongsToMany(Student::class, 'applications')
            ->withPivot(['cover_letter', 'message', 'attachments', 'created_at'])
            ->withTimestamps();
    }

	// Public roles must be open and have a closing date of today or later.
	public function scopeOpen($query) {
		return $query->where('status', 'open')->whereDate('closing_at', '>=', today());
	}

	// The board shows open internships. Companies also see their own closed ones and admins see all, so they can still manage them.
	public function scopeListedFor($query, $user = null) {
		if ($user && $user->isAdmin()) {
			return $query;
		}

		return $query->where(function ($query) use ($user) {
			$query->open();

			if ($user && $user->isCompany()) {
				$query->orWhere('company_id', $user->userable_id);
			}
		});
	}

	public function isOpen() {
		return $this->status !== 'closed' && $this->closing_at && ! $this->closing_at->copy()->startOfDay()->lt(today());
	}

	public function scopeWithFilters($query, $fields, $companies, $cities, $search) {
		return $query->when(count($fields), function ($query) use ($fields) {
				$query->whereIn('field_id', $fields);
			})
			->when(count($companies), function ($query) use ($companies) {
				$query->whereIn('company_id', $companies);
			})
			->when(count($cities), function ($query) use ($cities) {
				$query->whereIn('city_id', $cities);
			})
			->when(Str::length($search) > 0, function ($query) use ($search) {
				$query->where('title', 'like', "%{$search}%");
			});
	}
}
