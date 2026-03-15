<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Topico;
use App\Modules\ficheros\requests\TopicoStoreRequest;
use App\Modules\ficheros\requests\TopicoUpdateRequest;
use App\Modules\ficheros\services\TopicoService;
use Illuminate\Http\Request;

class TopicoController extends Controller
{
    public function __construct(private TopicoService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Topico::class);

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
        $this->authorize('create', Topico::class);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo(),
            ],
        ]);
    }

    public function store(TopicoStoreRequest $request)
    {
        $this->authorize('create', Topico::class);

        $topico = $this->service->create($request->validated());

        return response()->json(['data' => $topico], 201);
    }

    public function update(TopicoUpdateRequest $request, Topico $topico)
    {
        $this->authorize('update', $topico);

        $updated = $this->service->update($topico, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(Topico $topico)
    {
        $this->authorize('deactivate', $topico);

        $updated = $this->service->deactivate($topico);

        return response()->json(['data' => $updated]);
    }
}
