<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaServicio;

class TarifaServicioPolicy
{
    public function viewAny($user, Tarifa $tarifa): bool { return $user !== null; }
    public function create($user, Tarifa $tarifa): bool { return $user !== null; }
    public function update($user, TarifaServicio $servicio): bool { return $user !== null; }
    public function deactivate($user, TarifaServicio $servicio): bool { return $user !== null; }
}
