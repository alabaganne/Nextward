<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'image',
        'linkedin_profile_url',
        'userable_id',
        'userable_type',
        'is_admin'
    ];

    public $timestamps = false;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function userable() {
        return $this->morphTo();
    }

    public function isCompany() {
        return $this->userable_type === 'company';
    }

    public function isStudent() {
        return $this->userable_type === 'student';
    }

    public function isCandidate() {
        return $this->userable_type === 'student';
    }

    public function isAdmin() {
        return (bool) $this->is_admin && !$this->isCompany() && !$this->isStudent();
    }

    public function sentMessages() {
        return $this->hasMany(Message::class, 'from_id');
    }

    public function receivedMessages() {
        return $this->hasMany(Message::class, 'to_id');
    }

	public function receivesBroadcastNotificationsOn()
    {
        return 'user.'.$this->id;
    }
}
