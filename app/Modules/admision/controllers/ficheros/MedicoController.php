<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Medico;
use App\Modules\admision\requests\ficheros\MedicoStoreRequest;
use App\Modules\admision\requests\ficheros\MedicoUpdateRequest;
use App\Modules\admision\services\ficheros\MedicoService;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function __construct(private MedicoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Medico::class);

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

    public function store(MedicoStoreRequest $request)
    {
        $this->authorize('create', Medico::class);

        $medico = $this->service->create($request->validated());

        return response()->json(['data' => $medico], 201);
    }

    public function update(MedicoUpdateRequest $request, Medico $medico)
    {
        $this->authorize('update', $medico);

        $updated = $this->service->update($medico, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Medico $medico)
    {
        $this->authorize('deactivate', $medico);

        $updated = $this->service->deactivate($medico);

        return response()->json(['data' => $updated]);
    }
}
