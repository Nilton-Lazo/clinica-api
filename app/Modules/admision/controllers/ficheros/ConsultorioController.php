<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Consultorio;
use App\Modules\admision\requests\ficheros\ConsultorioStoreRequest;
use App\Modules\admision\requests\ficheros\ConsultorioUpdateRequest;
use App\Modules\admision\services\ficheros\ConsultorioService;
use Illuminate\Http\Request;

class ConsultorioController extends Controller
{
    public function __construct(private ConsultorioService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Consultorio::class);

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

    public function store(ConsultorioStoreRequest $request)
    {
        $this->authorize('create', Consultorio::class);

        $consultorio = $this->service->create($request->validated());

        return response()->json(['data' => $consultorio], 201);
    }

    public function update(ConsultorioUpdateRequest $request, Consultorio $consultorio)
    {
        $this->authorize('update', $consultorio);

        $updated = $this->service->update($consultorio, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Consultorio $consultorio)
    {
        $this->authorize('deactivate', $consultorio);

        $updated = $this->service->deactivate($consultorio);

        return response()->json(['data' => $updated]);
    }
}
