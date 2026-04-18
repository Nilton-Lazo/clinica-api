<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\CajaBancoTarjeta;

class CajaBancoTarjetaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, CajaBancoTarjeta $cajaBancoTarjeta): bool
    {
        return $user !== null;
    }

    public function deactivate($user, CajaBancoTarjeta $cajaBancoTarjeta): bool
    {
        return $user !== null;
    }
}
