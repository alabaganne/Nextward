<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class OpportunityPolicy
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
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Internship  $internship
     * @return mixed
     */
    public function view(?User $user, Opportunity $opportunity)
    {
        return $opportunity->publication_status === 'published'
            || ($user && $this->isOwner($user, $opportunity));
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        if (! $user->isCompany()) {
            return false;
        }

        $company = $user->userable;

        return $company
            && filled($company->about)
            && filled($company->website)
            && (filled($company->city_id) || filled($company->country_code) || filled($company->location));
    }

    /**
     * Determine whether the user owns a specific internship
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Internship  $internship
     * @return mixed
     */
    public function isOwner(User $user, Opportunity $opportunity)
    {
        return $user->isAdmin() || ($user->isCompany() && $user->userable?->id === $opportunity->company_id);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Internship  $internship
     * @return mixed
     */
    public function update(User $user, Opportunity $opportunity)
    {
        return $this->isOwner($user, $opportunity) && ($user->isAdmin() || $opportunity->origin === 'native');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Internship  $internship
     * @return mixed
     */
    public function delete(User $user, Opportunity $opportunity)
    {
        return $this->isOwner($user, $opportunity) && ($user->isAdmin() || $opportunity->origin === 'native');
    }
}
