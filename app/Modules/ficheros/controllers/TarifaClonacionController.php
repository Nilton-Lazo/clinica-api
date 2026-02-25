<?php

namespace App\Modules\ficheros\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\Tarifa;
use App\Modules\ficheros\requests\TarifaCloneFromBaseRequest;
use App\Modules\ficheros\services\TarifaClonacionService;

class TarifaClonacionController extends Controller
{
    public function __construct(private TarifaClonacionService $service) {}

    public function cloneFromBase(TarifaCloneFromBaseRequest $request, Tarifa $tarifa)
    {
        $this->authorize('update', $tarifa);

        $result = $this->service->cloneFromBase($tarifa, $request->validated());

        return response()->json(['data' => $result]);
    }
}

