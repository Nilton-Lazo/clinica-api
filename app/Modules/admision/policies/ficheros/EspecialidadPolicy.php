<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Especialidad;

class EspecialidadPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Especialidad $especialidad): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Especialidad $especialidad): bool
    {
        return $user !== null;
    }
}
