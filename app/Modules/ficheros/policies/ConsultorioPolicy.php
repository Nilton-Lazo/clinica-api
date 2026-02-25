<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Consultorio;

class ConsultorioPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Consultorio $consultorio): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Consultorio $consultorio): bool
    {
        return $user !== null;
    }
}

