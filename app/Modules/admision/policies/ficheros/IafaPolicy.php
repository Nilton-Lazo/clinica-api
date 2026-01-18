<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Iafa;

class IafaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Iafa $iafa): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Iafa $iafa): bool
    {
        return $user !== null;
    }
}
