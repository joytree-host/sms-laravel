<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $user->student?->id === $student->id;
        }

        if ($user->isParent()) {
            // Never trust a route parameter here — always re-derive the
            // guardian's authorized student set from their own
            // parent_student rows, the same pattern the Phase 1 parent
            // dashboard already uses.
            return in_array($student->id, $user->parentProfile?->studentIds() ?? [], true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }

    /** Enroll/withdraw subjects, assign class, change status — all admin-only mutations on a student record. */
    public function manageEnrollments(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }
}
