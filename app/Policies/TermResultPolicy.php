<?php

namespace App\Policies;

use App\Models\TermResult;
use App\Models\User;

/**
 * The publish gate lives here, not in Blade. A student/parent Gate::denies
 * on an unpublished result even with a perfectly-formed URL to it — see
 * view() below, which checks isPublished() before ownership even matters
 * for anyone who isn't an admin.
 */
class TermResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, TermResult $termResult): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $termResult->isPublished()) {
            return false;
        }

        if ($user->isStudent()) {
            return $user->student?->id === $termResult->student_id;
        }

        if ($user->isParent()) {
            return in_array($termResult->student_id, $user->parentProfile?->studentIds() ?? [], true);
        }

        return false;
    }

    /** Triggering a (re)compute for a class+term — a class-level action, not tied to one TermResult instance. */
    public function compute(User $user): bool
    {
        return $user->isAdmin();
    }

    public function publish(User $user, TermResult $termResult): bool
    {
        return $user->isAdmin();
    }

    public function unpublish(User $user, TermResult $termResult): bool
    {
        return $user->isAdmin();
    }
}
