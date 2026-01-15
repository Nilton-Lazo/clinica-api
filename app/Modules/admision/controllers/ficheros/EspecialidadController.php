<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Especialidad;
use App\Modules\admision\requests\ficheros\EspecialidadStoreRequest;
use App\Modules\admision\requests\ficheros\EspecialidadUpdateRequest;
use App\Modules\admision\services\ficheros\EspecialidadService;
use Illuminate\Http\Request;

class EspecialidadController extends Controller
{
    public function __construct(private EspecialidadService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Especialidad::class);

        $p = $this->service->paginate($request->only(['q', 'status', 'per_page', 'page']));

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

    public function nextCodigo()
    {
        $this->authorize('create', Especialidad::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(EspecialidadStoreRequest $request)
    {
        $this->authorize('create', Especialidad::class);

        $especialidad = $this->service->create($request->validated());

        return response()->json(['data' => $especialidad], 201);
    }

    public function update(EspecialidadUpdateRequest $request, Especialidad $especialidad)
    {
        $this->authorize('update', $especialidad);

        $updated = $this->service->update($especialidad, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Especialidad $especialidad)
    {
        $this->authorize('deactivate', $especialidad);

        $updated = $this->service->deactivate($especialidad);

        return response()->json(['data' => $updated]);
    }
}