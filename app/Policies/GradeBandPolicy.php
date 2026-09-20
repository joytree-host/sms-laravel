<?php

namespace App\Policies;

use App\Models\GradeBand;
use App\Models\User;

class GradeBandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, GradeBand $gradeBand): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, GradeBand $gradeBand): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, GradeBand $gradeBand): bool
    {
        return $user->isAdmin();
    }
}
