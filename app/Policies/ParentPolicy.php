<?php

namespace App\Policies;

use App\Models\ParentGuardian;
use App\Models\User;

class ParentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ParentGuardian $parentGuardian): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isParent() && $user->parentProfile?->id === $parentGuardian->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ParentGuardian $parentGuardian): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ParentGuardian $parentGuardian): bool
    {
        return $user->isAdmin();
    }
}
