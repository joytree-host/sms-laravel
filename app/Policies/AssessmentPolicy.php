<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;

class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin();
    }

    /** Entering/updating student scores for this assessment. */
    public function manageScores(User $user, Assessment $assessment): bool
    {
        return $user->isAdmin();
    }
}
