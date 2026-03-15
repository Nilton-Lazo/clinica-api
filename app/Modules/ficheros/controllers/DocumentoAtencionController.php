<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\DocumentoAtencion;
use App\Modules\ficheros\requests\DocumentoAtencionStoreRequest;
use App\Modules\ficheros\requests\DocumentoAtencionUpdateRequest;
use App\Modules\ficheros\services\DocumentoAtencionService;
use Illuminate\Http\Request;

class DocumentoAtencionController extends Controller
{
    public function __construct(private DocumentoAtencionService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', DocumentoAtencion::class);

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

    public function store(DocumentoAtencionStoreRequest $request)
    {
        $this->authorize('create', DocumentoAtencion::class);

        $documentoAtencion = $this->service->create($request->validated());

        return response()->json(['data' => $documentoAtencion], 201);
    }

    public function update(DocumentoAtencionUpdateRequest $request, DocumentoAtencion $documentoAtencion)
    {
        $this->authorize('update', $documentoAtencion);

        $updated = $this->service->update($documentoAtencion, $request->validated());

        return response()->json(['data' => $updated]);
    }

    public function deactivate(DocumentoAtencion $documentoAtencion)
    {
        $this->authorize('deactivate', $documentoAtencion);

        $this->service->deactivate($documentoAtencion);

        return response()->json(['data' => $documentoAtencion->fresh()]);
    }
}
