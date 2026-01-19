<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;

class TarifaCategoriaPolicy
{
    public function viewAny($user, Tarifa $tarifa): bool { return $user !== null; }
    public function create($user, Tarifa $tarifa): bool { return $user !== null; }
    public function update($user, TarifaCategoria $categoria): bool { return $user !== null; }
    public function deactivate($user, TarifaCategoria $categoria): bool { return $user !== null; }
}
