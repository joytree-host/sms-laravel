<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $user->student?->id === $attendance->student_id;
        }

        if ($user->isParent()) {
            return in_array($attendance->student_id, $user->parentProfile?->studentIds() ?? [], true);
        }

        return false;
    }

    /** Marking/bulk-saving attendance for a class is an admin-only action, not tied to one Attendance instance. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->isAdmin();
    }
}
