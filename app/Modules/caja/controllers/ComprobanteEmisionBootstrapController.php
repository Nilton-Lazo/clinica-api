<?php

namespace App\Modules\caja\controllers;

use App\Http\Controllers\Controller;
use App\Modules\admision\models\CajaBancoTarjeta;
use App\Modules\admision\models\CajaFormaPago;
use App\Modules\admision\models\CajaMedioPago;
use App\Modules\admision\models\CajaNumeracionComprobante;
use App\Modules\caja\support\ComprobanteEmisionCatalogPayload;
use App\Modules\ficheros\services\CajaBancoTarjetaService;
use App\Modules\ficheros\services\CajaFormaPagoService;
use App\Modules\ficheros\services\CajaMedioPagoService;
use App\Modules\ficheros\services\CajaNumeracionComprobanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ComprobanteEmisionBootstrapController extends Controller
{
    private const CATALOG_LIMIT = 2000;

    public function __construct(
        private CajaFormaPagoService $formaPago,
        private CajaMedioPagoService $medioPago,
        private CajaBancoTarjetaService $bancoTarjeta,
        private CajaNumeracionComprobanteService $numeracion,
    ) {}

    private static function cacheKey(): string
    {
        return 'caja:emision-comprobantes:bootstrap:v3';
    }

    private static function cacheTtlSeconds(): int
    {
        return 20;
    }

    public function bootstrap(): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);
        $this->authorize('viewAny', CajaMedioPago::class);
        $this->authorize('viewAny', CajaBancoTarjeta::class);
        $this->authorize('viewAny', CajaNumeracionComprobante::class);

        $payload = Cache::remember(self::cacheKey(), self::cacheTtlSeconds(), function () {
            $formas = $this->formaPago->listAllActivosForEmision(self::CATALOG_LIMIT);
            $medios = $this->medioPago->listAllActivosForEmision(self::CATALOG_LIMIT);
            $bancos = $this->bancoTarjeta->listAllActivosForEmision(self::CATALOG_LIMIT);
            $numeraciones = $this->numeracion->listAllActivosForEmision(self::CATALOG_LIMIT);

            return [
                'catalog' => ComprobanteEmisionCatalogPayload::build(),
                'formas' => $formas,
                'medios' => $medios,
                'bancos' => $bancos,
                'numeraciones' => $numeraciones,
            ];
        });

        return response()->json($payload)->header('Cache-Control', 'private, max-age=15');
    }
}
