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

    public function paisesList()
    {
        return response()->json([
            'data' => $this->service->paisesList(),
        ]);
    }

    public function ubigeos(Request $request)
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 50);
        $q = $request->get('q');

        if ($page === 1 && ($q === null || trim((string) $q) === '')) {
            $cached = $this->service->ubigeosFirstPage($perPage);
            return response()->json([
                'data' => $cached,
                'meta' => [
                    'current_page' => 1,
                    'per_page' => count($cached),
                    'total' => count($cached),
                    'last_page' => 1,
                ],
            ]);
        }

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
