<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\GrupoServicio;

class GrupoServicioController extends Controller
{
    public function lookup()
    {
        $items = GrupoServicio::activos()
            ->orderBy('orden')
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'descripcion', 'abrev']);

        return response()->json([
            'data' => $items->map(fn ($g) => [
                'id' => (int)$g->id,
                'codigo' => (string)$g->codigo,
                'descripcion' => (string)$g->descripcion,
                'abrev' => $g->abrev ? (string)$g->abrev : null,
            ]),
        ]);
    }
}
