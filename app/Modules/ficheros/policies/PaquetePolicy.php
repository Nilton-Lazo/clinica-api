<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Paquete;

class PaquetePolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Paquete $paquete): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Paquete $paquete): bool
    {
        return $user !== null;
    }
}
