<?php

namespace App\Modules\caja\controllers;

use App\Core\support\RecordStatus;
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

    private static function listFilters(): array
    {
        return [
            'page' => 1,
            'per_page' => 100,
            'status' => RecordStatus::ACTIVO->value,
        ];
    }

    public function bootstrap(): JsonResponse
    {
        $this->authorize('viewAny', CajaFormaPago::class);
        $this->authorize('viewAny', CajaMedioPago::class);
        $this->authorize('viewAny', CajaBancoTarjeta::class);
        $this->authorize('viewAny', CajaNumeracionComprobante::class);

        $payload = Cache::remember(self::cacheKey(), self::cacheTtlSeconds(), function () {
            $f = self::listFilters();

            $formasPaginator = $this->formaPago->paginate($f);
            $formas = array_map(
                static fn ($row) => $row->toArray(),
                $formasPaginator->items()
            );

            $mediosPaginator = $this->medioPago->paginate($f);
            $medios = $this->medioPago->serializePage($mediosPaginator)['data'];

            $bancosPaginator = $this->bancoTarjeta->paginate($f);
            $bancos = $this->bancoTarjeta->serializePage($bancosPaginator)['data'];

            $numeraciones = $this->numeracion->listAllActivosForEmision();

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
