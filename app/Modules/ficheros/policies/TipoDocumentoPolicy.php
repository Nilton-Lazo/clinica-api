<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\TipoDocumento;

class TipoDocumentoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, TipoDocumento $tipoDocumento): bool
    {
        return $user !== null;
    }

    public function deactivate($user, TipoDocumento $tipoDocumento): bool
    {
        return $user !== null;
    }
}
