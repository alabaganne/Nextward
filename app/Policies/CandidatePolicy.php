<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CandidatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function view(User $user, Candidate $candidate)
    {
        return $user->isAdmin()
            || ($user->isCandidate() && $user->userable_id === $candidate->id)
            || ($user->isCompany() && $user->userable_id && Application::where('candidate_id', $candidate->id)
                ->where('company_id', $user->userable_id)
                ->where('channel', 'internal')
                ->exists());
    }

    public function create(User $user)
    {
        return $user->isAdmin();
    }

    public function update(User $user, Candidate $candidate)
    {
        return $user->isAdmin() || ($user->isCandidate() && $user->userable_id === $candidate->id);
    }

    public function delete(User $user, Candidate $candidate)
    {
        return $user->isAdmin();
    }
}
