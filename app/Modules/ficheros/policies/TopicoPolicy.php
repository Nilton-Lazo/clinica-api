<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\Topico;

class TopicoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, Topico $topico): bool
    {
        return $user !== null;
    }

    public function deactivate($user, Topico $topico): bool
    {
        return $user !== null;
    }
}
