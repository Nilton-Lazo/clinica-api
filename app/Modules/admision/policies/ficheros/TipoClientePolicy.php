<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\TipoCliente;

class TipoClientePolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, TipoCliente $tipoCliente): bool
    {
        return $user !== null;
    }

    public function deactivate($user, TipoCliente $tipoCliente): bool
    {
        return $user !== null;
    }
}
