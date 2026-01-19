<?php

namespace App\Modules\admision\controllers\ficheros;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\admision\models\TarifaSubcategoria;
use App\Modules\admision\requests\ficheros\TarifaSubcategoriaStoreRequest;
use App\Modules\admision\requests\ficheros\TarifaSubcategoriaUpdateRequest;
use App\Modules\admision\services\ficheros\TarifaSubcategoriaService;
use Illuminate\Http\Request;

class TarifaSubcategoriaController extends Controller
{
    public function __construct(private TarifaSubcategoriaService $service) {}

    private function present(TarifaSubcategoria $s): array
    {
        return [
            'id' => (int)$s->id,
            'tarifa_id' => (int)$s->tarifa_id,
            'categoria_id' => (int)$s->categoria_id,
            'codigo' => (string)$s->codigo,
            'descripcion' => (string)$s->nombre,
            'estado' => (string)$s->estado,
            'created_at' => $s->created_at,
            'updated_at' => $s->updated_at,
        ];
    }

    public function index(Tarifa $tarifa, Request $request)
    {
        $this->authorize('viewAny', [TarifaSubcategoria::class, $tarifa]);

        $p = $this->service->paginate($tarifa, $request->only(['q', 'status', 'categoria_id', 'per_page', 'page']));

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
        $this->authorize('viewAny', [TarifaSubcategoria::class, $tarifa]);

        $categoriaId = (int)$request->query('categoria_id', 0);
        $onlyActivas = filter_var($request->query('only_active', '1'), FILTER_VALIDATE_BOOL);

        return response()->json([
            'data' => $this->service->lookup($tarifa, $categoriaId, $onlyActivas),
        ]);
    }

    public function nextCodigo(Tarifa $tarifa, Request $request)
    {
        $this->authorize('create', [TarifaSubcategoria::class, $tarifa]);

        $categoriaId = (int)$request->query('categoria_id', 0);

        return response()->json([
            'data' => [
                'codigo' => $this->service->peekNextCodigo($tarifa, $categoriaId),
            ],
        ]);
    }

    public function store(Tarifa $tarifa, TarifaSubcategoriaStoreRequest $request)
    {
        $this->authorize('create', [TarifaSubcategoria::class, $tarifa]);

        $created = $this->service->create($tarifa, $request->validated());

        return response()->json(['data' => $this->present($created)], 201);
    }

    public function update(Tarifa $tarifa, TarifaSubcategoriaUpdateRequest $request, TarifaSubcategoria $subcategoria)
    {
        $this->authorize('update', $subcategoria);

        $updated = $this->service->update($tarifa, $subcategoria, $request->validated());

        return response()->json(['data' => $this->present($updated)]);
    }

    public function deactivate(Tarifa $tarifa, TarifaSubcategoria $subcategoria)
    {
        $this->authorize('deactivate', $subcategoria);

        $updated = $this->service->deactivate($tarifa, $subcategoria);

        return response()->json(['data' => $this->present($updated)]);
    }
}
