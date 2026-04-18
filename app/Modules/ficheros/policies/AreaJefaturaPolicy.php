<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\AreaJefatura;

class AreaJefaturaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, AreaJefatura $areaJefatura): bool
    {
        return $user !== null;
    }

    public function deactivate($user, AreaJefatura $areaJefatura): bool
    {
        return $user !== null;
    }
}
