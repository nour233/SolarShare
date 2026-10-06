<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /** The reporter and the admins can open an incident and write in it. */
    public function view(User $user, Incident $incident): bool
    {
        return $user->isAdmin() || $incident->isReporter($user);
    }
}
