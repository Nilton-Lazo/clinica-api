<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Turno;
use App\Modules\admision\requests\ficheros\TurnoStoreRequest;
use App\Modules\admision\requests\ficheros\TurnoUpdateRequest;
use App\Modules\admision\services\ficheros\TurnoService;
use Illuminate\Http\Request;

class TurnoController extends Controller
{
    public function __construct(private TurnoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Turno::class);

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
        $this->authorize('create', Turno::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(TurnoStoreRequest $request)
    {
        $this->authorize('create', Turno::class);

        $turno = $this->service->create($request->validated());

        return response()->json(['data' => $turno], 201);
    }

    public function update(TurnoUpdateRequest $request, Turno $turno)
    {
        $this->authorize('update', $turno);

        $updated = $this->service->update($turno, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Turno $turno)
    {
        $this->authorize('deactivate', $turno);

        $updated = $this->service->deactivate($turno);

        return response()->json(['data' => $updated]);
    }
}
