<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Contratante;

class ContratantePolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Contratante $contratante): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Contratante $contratante): bool
    {
        return $user !== null;
    }
}

