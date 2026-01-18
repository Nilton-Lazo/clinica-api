<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Iafa;
use App\Modules\admision\requests\ficheros\IafaStoreRequest;
use App\Modules\admision\requests\ficheros\IafaUpdateRequest;
use App\Modules\admision\services\ficheros\IafaService;
use Illuminate\Http\Request;

class IafaController extends Controller
{
    public function __construct(private IafaService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Iafa::class);

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
        $this->authorize('create', Iafa::class);

        $codigo = $this->service->previewNextCodigo();

        return response()->json([
            'data' => ['codigo' => $codigo],
        ]);
    }

    public function store(IafaStoreRequest $request)
    {
        $this->authorize('create', Iafa::class);

        $iafa = $this->service->create($request->validated());

        return response()->json(['data' => $iafa], 201);
    }

    public function update(IafaUpdateRequest $request, Iafa $iafa)
    {
        $this->authorize('update', $iafa);

        $updated = $this->service->update($iafa, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Iafa $iafa)
    {
        $this->authorize('deactivate', $iafa);

        $updated = $this->service->deactivate($iafa);

        return response()->json(['data' => $updated]);
    }
}
