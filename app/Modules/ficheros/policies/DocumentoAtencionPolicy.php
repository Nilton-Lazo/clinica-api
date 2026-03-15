<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\DocumentoAtencion;

class DocumentoAtencionPolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, DocumentoAtencion $documentoAtencion): bool
    {
        return $user !== null;
    }

    public function deactivate($user, DocumentoAtencion $documentoAtencion): bool
    {
        return $user !== null;
    }
}
