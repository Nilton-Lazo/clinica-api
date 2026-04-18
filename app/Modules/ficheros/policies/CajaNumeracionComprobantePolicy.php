<?php

namespace App\Modules\ficheros\policies;

use App\Modules\admision\models\CajaNumeracionComprobante;

class CajaNumeracionComprobantePolicy
{
    public function viewAny($user): bool
    {
        return $user !== null;
    }

    public function create($user): bool
    {
        return $user !== null;
    }

    public function update($user, CajaNumeracionComprobante $cajaNumeracionComprobante): bool
    {
        return $user !== null;
    }

    public function deactivate($user, CajaNumeracionComprobante $cajaNumeracionComprobante): bool
    {
        return $user !== null;
    }
}
