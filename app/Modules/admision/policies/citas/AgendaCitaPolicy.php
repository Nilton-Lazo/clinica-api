<?php

namespace App\Modules\admision\policies\citas;

use App\Modules\admision\models\AgendaCita;

class AgendaCitaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, AgendaCita $cita): bool
    {
        return $user !== null;
    }
}
