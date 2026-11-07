<?php

namespace App\Policies;

use App\Models\Mostra;
use App\Models\User;

class MostraPolicy
{
    private function habilitat(User $user, array $rols): bool
    {
        return (bool) $user->actiu && in_array($user->rol, $rols, true);
    }

    public function viewAny(User $user): bool
    {
        return $this->habilitat($user, ['admin', 'tecnic', 'patoleg']);
    }

    public function view(User $user, Mostra $mostra): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->habilitat($user, ['admin', 'tecnic']);
    }

    public function update(User $user, Mostra $mostra): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Mostra $mostra): bool
    {
        return $this->habilitat($user, ['admin']);
    }

    public function process(User $user, Mostra $mostra): bool
    {
        return $this->create($user);
    }

    public function diagnose(User $user, Mostra $mostra): bool
    {
        return $this->habilitat($user, ['admin', 'patoleg']);
    }

    public function archive(User $user, Mostra $mostra): bool
    {
        return $this->diagnose($user, $mostra);
    }
}
