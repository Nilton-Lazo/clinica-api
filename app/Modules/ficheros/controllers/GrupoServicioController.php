<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\GrupoServicio;
use Illuminate\Support\Facades\Cache;

class GrupoServicioController extends Controller
{
    private const LOOKUP_CACHE_TTL_SECONDS = 120;

    public function lookup()
    {
        $data = Cache::remember('tarifario:grupos-servicio', self::LOOKUP_CACHE_TTL_SECONDS, function () {
            $items = GrupoServicio::activos()
                ->orderBy('orden')
                ->orderBy('codigo')
                ->get(['id', 'codigo', 'descripcion', 'abrev']);

            return $items->map(fn ($g) => [
                'id' => (int)$g->id,
                'codigo' => (string)$g->codigo,
                'descripcion' => (string)$g->descripcion,
                'abrev' => $g->abrev ? (string)$g->abrev : null,
            ])->values()->all();
        });

        return response()->json(['data' => $data]);
    }
}

