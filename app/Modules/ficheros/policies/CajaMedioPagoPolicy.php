<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\CajaMedioPago;

class CajaMedioPagoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, CajaMedioPago $cajaMedioPago): bool
    {
        return $user !== null;
    }

    public function deactivate($user, CajaMedioPago $cajaMedioPago): bool
    {
        return $user !== null;
    }
}
