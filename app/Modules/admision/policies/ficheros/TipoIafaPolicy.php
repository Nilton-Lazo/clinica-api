<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\TipoIafa;

class TipoIafaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, TipoIafa $tipoIafa): bool
    {
        return $user !== null;
    }

    public function deactivate($user, TipoIafa $tipoIafa): bool
    {
        return $user !== null;
    }
}
