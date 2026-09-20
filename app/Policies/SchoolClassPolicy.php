<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $user->student?->class_id === $schoolClass->id;
        }

        if ($user->isParent()) {
            $childClassIds = $user->parentProfile?->students()->pluck('class_id')->all() ?? [];

            return in_array($schoolClass->id, $childClassIds, true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        return $user->isAdmin();
    }

    /** Assigning subjects to a class is an admin-only structural change. */
    public function manageSubjects(User $user, SchoolClass $schoolClass): bool
    {
        return $user->isAdmin();
    }
}
