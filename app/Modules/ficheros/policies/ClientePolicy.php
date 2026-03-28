<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Cliente;

class ClientePolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Cliente $cliente): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Cliente $cliente): bool
    {
        return $user !== null;
    }
}
