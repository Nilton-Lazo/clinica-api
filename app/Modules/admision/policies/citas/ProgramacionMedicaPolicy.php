<?php

namespace App\Modules\admision\policies\citas;

use App\Modules\admision\models\ProgramacionMedica;

class ProgramacionMedicaPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, ProgramacionMedica $pm): bool
    {
        return $user !== null;
    }

    public function deactivate($user, ProgramacionMedica $pm): bool
    {
        return $user !== null;
    }
}
