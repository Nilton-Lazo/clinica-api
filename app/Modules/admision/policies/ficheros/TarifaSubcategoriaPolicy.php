<?php

namespace App\Modules\admision\policies\ficheros;

use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaSubcategoria;

class TarifaSubcategoriaPolicy
{
    public function viewAny($user, Tarifa $tarifa): bool { return $user !== null; }
    public function create($user, Tarifa $tarifa): bool { return $user !== null; }
    public function update($user, TarifaSubcategoria $subcategoria): bool { return $user !== null; }
    public function deactivate($user, TarifaSubcategoria $subcategoria): bool { return $user !== null; }
}
