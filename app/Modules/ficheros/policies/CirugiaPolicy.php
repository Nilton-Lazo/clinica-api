<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Cirugia;

class CirugiaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Cirugia $cirugia): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Cirugia $cirugia): bool
    {
        return $user !== null;
    }
}
