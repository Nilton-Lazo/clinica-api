<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\CajaFormaPago;

class CajaFormaPagoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, CajaFormaPago $cajaFormaPago): bool
    {
        return $user !== null;
    }

    public function deactivate($user, CajaFormaPago $cajaFormaPago): bool
    {
        return $user !== null;
    }
}
