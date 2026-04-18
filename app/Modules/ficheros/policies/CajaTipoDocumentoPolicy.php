<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\CajaTipoDocumento;

class CajaTipoDocumentoPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, CajaTipoDocumento $cajaTipoDocumento): bool
    {
        return $user !== null;
    }

    public function deactivate($user, CajaTipoDocumento $cajaTipoDocumento): bool
    {
        return $user !== null;
    }
}
