<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function view(User $user, Student $student)
    {
        return $user->isAdmin() || ($user->isCandidate() && $user->userable_id === $student->id);
    }

    public function create(User $user)
    {
        return $user->isAdmin();
    }

    public function update(User $user, Student $student)
    {
        return $user->isAdmin() || ($user->isCandidate() && $user->userable_id === $student->id);
    }

    public function delete(User $user, Student $student)
    {
        return $user->isAdmin();
    }
}
