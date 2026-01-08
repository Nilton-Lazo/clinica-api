<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Turno;

class TurnoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Turno $turno): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Turno $turno): bool
    {
        return $user !== null;
    }
}
