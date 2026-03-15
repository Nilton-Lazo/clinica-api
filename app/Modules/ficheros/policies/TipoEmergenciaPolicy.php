<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\TipoEmergencia;

class TipoEmergenciaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, TipoEmergencia $tipoEmergencia): bool
    {
        return $user !== null;
    }

    public function deactivate($user, TipoEmergencia $tipoEmergencia): bool
    {
        return $user !== null;
    }
}
