<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ApplicationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return $user->isAdmin() || $user->isCandidate() || $user->isCompany();
    }

    public function belongsTo(User $user, Application $application)
    {
        return $user->isAdmin()
            || ($user->isCandidate() && $user->userable_id === $application->student_id)
            || ($application->channel === 'internal' && $user->isCompany() && $user->userable_id === $application->company_id);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Application  $application
     * @return mixed
     */
    public function view(User $user, Application $application)
    {
        return $this->belongsTo($user, $application);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        $internship = Route::current()->internship;

        if(!$user->isCandidate() || !$internship || $internship->application_mode !== 'internal' || !$internship->company?->user || ! $internship->isOpen() || Application::where('internship_id', $internship->id)->where('student_id', $user->userable_id)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Application  $application
     * @return mixed
     */
    public function update(User $user, Application $application)
    {
        return $application->channel === 'internal' && ($user->isAdmin() || ($user->isCandidate() && $user->userable_id === $application->student_id && ! in_array($application->status, ['offer', 'closed'], true)));
    }

    public function reply(User $user, Application $application)
    {
        return $application->channel === 'internal' && ($user->isAdmin() || ($user->isCompany() && $user->userable_id === $application->company_id));
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Application  $application
     * @return mixed
     */
    public function delete(User $user, Application $application)
    {
        return $user->isAdmin() || ($user->isCandidate() && $user->userable_id === $application->student_id && ($application->channel === 'external' || ! in_array($application->status, ['offer', 'closed'], true)));
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Application  $application
     * @return mixed
     */
    public function restore(User $user, Application $application)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Application  $application
     * @return mixed
     */
    public function forceDelete(User $user, Application $application)
    {
        //
    }
}
