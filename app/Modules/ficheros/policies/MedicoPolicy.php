<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Medico;

class MedicoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Medico $medico): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Medico $medico): bool
    {
        return $user !== null;
    }
}

