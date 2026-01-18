<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Tarifa;

class TarifaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Tarifa $tarifa): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Tarifa $tarifa): bool
    {
        return $user !== null;
    }
}
