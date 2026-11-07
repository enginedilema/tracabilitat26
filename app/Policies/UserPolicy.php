<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return (bool) $actor->actiu && $actor->rol === 'admin';
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->viewAny($actor);
    }
}
