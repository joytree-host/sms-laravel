<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

/**
 * The subject catalog itself (code, name) isn't sensitive
 * per-student data — any authenticated user can view it, the same way a
 * school's course catalog is generally visible to its own students and
 * parents. Only admins may create/edit/delete subjects or decide which
 * subjects a given class teaches.
 */
class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Subject $subject): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->isAdmin();
    }
}
