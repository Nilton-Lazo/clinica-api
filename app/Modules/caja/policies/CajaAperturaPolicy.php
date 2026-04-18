<?php

namespace App\Modules\caja\policies;

use App\Modules\caja\models\CajaApertura;

class CajaAperturaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user): bool
    {
        return $user !== null;
    }

    public function view($user, CajaApertura $cajaApertura): bool
    {
        return $user !== null;
    }
}
