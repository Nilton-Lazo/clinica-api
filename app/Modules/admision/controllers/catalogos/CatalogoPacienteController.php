<?php

namespace App\Modules\admision\controllers\catalogos;

use App\Http\Controllers\Controller;
use App\Modules\admision\services\catalogos\CatalogoPacienteService;
use Illuminate\Http\Request;

class CatalogoPacienteController extends Controller
{
    public function __construct(private CatalogoPacienteService $service) {}

    public function pacienteForm()
    {
        return response()->json([
            'data' => $this->service->pacienteForm(),
        ]);
    }

    public function paises(Request $request)
    {
        $p = $this->service->paises($request->only(['q', 'per_page', 'page']));

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function ubigeos(Request $request)
    {
        $p = $this->service->ubigeos($request->only(['q', 'per_page', 'page']));

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }
}
