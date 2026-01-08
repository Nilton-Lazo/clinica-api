<?php

namespace App\Modules\admision\policies\ficheros;

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
