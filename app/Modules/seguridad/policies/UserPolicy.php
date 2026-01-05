<?php

namespace App\Modules\seguridad\Policies;

use App\Models\User;

class UserPolicy
{
    public function create(User $actor): bool
    {
        return $actor->estado === 'activo'
            && $actor->nivel === 'administrador';
    }
}
