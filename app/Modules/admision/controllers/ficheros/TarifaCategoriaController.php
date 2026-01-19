<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaCategoria;
use App\Modules\admision\requests\ficheros\TarifaCategoriaStoreRequest;
use App\Modules\admision\requests\ficheros\TarifaCategoriaUpdateRequest;
use App\Modules\admision\services\ficheros\TarifaCategoriaService;
use Illuminate\Http\Request;

class TarifaCategoriaController extends Controller
{
    public function __construct(private TarifaCategoriaService $service) {}

    private function present(TarifaCategoria $c): array
    {
        return [
            'id' => (int)$c->id,
            'tarifa_id' => (int)$c->tarifa_id,
            'codigo' => (string)$c->codigo,
            'descripcion' => (string)$c->nombre,
            'estado' => (string)$c->estado,
            'created_at' => $c->created_at,
            'updated_at' => $c->updated_at,
        ];
    }

    public function index(Tarifa $tarifa, Request $request)
    {
        $this->authorize('viewAny', [TarifaCategoria::class, $tarifa]);

        $p = $this->service->paginate($tarifa, $request->only(['q', 'status', 'per_page', 'page']));

        return response()->json([
            'data' => array_map(fn ($x) => $this->present($x), $p->items()),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    public function lookup(Tarifa $tarifa, Request $request)
    {
        $this->authorize('viewAny', [TarifaCategoria::class, $tarifa]);

        $onlyActivas = filter_var($request->query('only_active', '1'), FILTER_VALIDATE_BOOL);

        return response()->json([
            'data' => $this->service->lookup($tarifa, $onlyActivas),
        ]);
    }

    public function nextCodigo(Tarifa $tarifa)
    {
        $this->authorize('create', [TarifaCategoria::class, $tarifa]);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo($tarifa),
            ],
        ]);
    }

    public function store(Tarifa $tarifa, TarifaCategoriaStoreRequest $request)
    {
        $this->authorize('create', [TarifaCategoria::class, $tarifa]);

        $created = $this->service->create($tarifa, $request->validated());

        return response()->json(['data' => $this->present($created)], 201);
    }

    public function update(Tarifa $tarifa, TarifaCategoriaUpdateRequest $request, TarifaCategoria $categoria)
    {
        $this->authorize('update', $categoria);

        $updated = $this->service->update($tarifa, $categoria, $request->validated());

        return response()->json(['data' => $this->present($updated)]);
    }

    public function deactivate(Tarifa $tarifa, TarifaCategoria $categoria)
    {
        $this->authorize('deactivate', $categoria);

        $updated = $this->service->deactivate($tarifa, $categoria);

        return response()->json(['data' => $this->present($updated)]);
    }
}
