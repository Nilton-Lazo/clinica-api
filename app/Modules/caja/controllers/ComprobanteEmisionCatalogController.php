<?php

namespace App\Modules\caja\controllers;

use App\Http\Controllers\Controller;
use App\Modules\caja\support\ComprobanteEmisionCatalogPayload;
use Illuminate\Http\JsonResponse;

class ComprobanteEmisionCatalogController extends Controller
{
    public function catalogo(): JsonResponse
    {
        return response()->json(ComprobanteEmisionCatalogPayload::build());
    }
}
