<?php

namespace App\Modules\caja\services;

use App\Core\audit\AuditService;
use App\Core\realtime\RealtimeBroadcaster;
use App\Models\User;
use App\Modules\admision\models\AreaJefatura;
use App\Modules\caja\models\CajaApertura;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaAperturaService
{
    private const CODIGO_MIN = 1;

    private const CODIGO_MAX = 9_999_999_999;

    public function __construct(
        private AuditService $audit,
        private RealtimeBroadcaster $realtime,
    ) {}

    private function allocateNextCodigoSerial(): string
    {
        $last = CajaApertura::query()
            ->whereRaw('codigo ~ ?', ['^[0-9]{10}$'])
            ->lockForUpdate()
            ->orderByRaw('codigo::bigint DESC')
            ->first();

        $next = self::CODIGO_MIN;
        if ($last && is_string($last->codigo) && ctype_digit($last->codigo)) {
            $cur = (int) $last->codigo;
            if ($cur >= self::CODIGO_MIN && $cur < self::CODIGO_MAX) {
                $next = $cur + 1;
            }
        }

        if ($next > self::CODIGO_MAX) {
            throw ValidationException::withMessages([
                'codigo' => ['No hay códigos de apertura disponibles para crear una nueva caja.'],
            ]);
        }

        return str_pad((string) $next, 10, '0', STR_PAD_LEFT);
    }

    public function peekNextCodigo(): string
    {
        return DB::transaction(fn () => $this->allocateNextCodigoSerial());
    }

    public function create(array $data, User $actor): CajaApertura
    {
        return DB::transaction(function () use ($data, $actor) {
            $tz = (string) config('app.timezone');
            $aperturaAt = Carbon::now($tz);
            $tipo = (string) ($data['tipo'] ?? '');

            $alreadyOpened = CajaApertura::query()
                ->where('user_recepciona_id', $actor->id)
                ->where('tipo', $tipo)
                ->whereNull('cerrada_at')
                ->lockForUpdate()
                ->exists();

            if ($alreadyOpened) {
                throw ValidationException::withMessages([
                    'tipo' => ['Ya tienes una caja '.strtolower($tipo).' aperturada. Debes cerrarla antes de abrir otra.'],
                ]);
            }

            $codigo = $this->allocateNextCodigoSerial();

            $entregaExists = User::query()
                ->whereKey($data['user_entrega_id'])
                ->where('estado', 'activo')
                ->exists();
            if (! $entregaExists) {
                throw ValidationException::withMessages([
                    'user_entrega_id' => ['El personal que entrega no existe o no está activo. Selecciona otro usuario.'],
                ]);
            }
            $recepciona = $actor;

            $areaExists = AreaJefatura::query()
                ->whereKey($data['area_jefatura_id'])
                ->where('estado', 'ACTIVO')
                ->exists();
            if (! $areaExists) {
                throw ValidationException::withMessages([
                    'area_jefatura_id' => ['El área o jefatura seleccionada no existe o no está activa. Selecciona otra opción.'],
                ]);
            }

            $row = CajaApertura::create([
                'codigo' => $codigo,
                'tipo' => $data['tipo'],
                'user_entrega_id' => $data['user_entrega_id'],
                'user_recepciona_id' => $recepciona->id,
                'area_jefatura_id' => $data['area_jefatura_id'],
                'moneda' => 'PEN',
                'monto_inicio' => $data['monto_inicio'],
                'usuario_caja' => $recepciona->username,
                'observaciones' => $data['observaciones'] ?? null,
                'apertura_at' => $aperturaAt,
            ]);

            $this->audit->log(
                'operational.caja.apertura.create',
                'Apertura de caja',
                'caja_apertura',
                (string) $row->id,
                [
                    'codigo' => $row->codigo,
                    'tipo' => $row->tipo,
                    'monto_inicio' => (string) $row->monto_inicio,
                    'apertura_at' => $row->apertura_at?->toIso8601String(),
                ],
                'success',
                201
            );

            $this->realtime->entityChanged(
                module: 'caja',
                entity: 'caja_apertura',
                action: 'created',
                id: (int) $row->id,
                scope: strtolower((string) $row->tipo),
                metadata: ['codigo' => $row->codigo, 'tipo' => $row->tipo],
                actorId: (int) $actor->id,
            );

            return $row->load(['userEntrega', 'userRecepciona', 'areaJefatura']);
        });
    }

    public function close(array $data, User $actor): CajaApertura
    {
        return DB::transaction(function () use ($data, $actor) {
            $tz = (string) config('app.timezone');
            $tipo = (string) ($data['tipo'] ?? '');

            $open = CajaApertura::query()
                ->where('user_recepciona_id', $actor->id)
                ->where('tipo', $tipo)
                ->whereNull('cerrada_at')
                ->lockForUpdate()
                ->orderByDesc('apertura_at')
                ->first();

            if (!$open) {
                throw ValidationException::withMessages([
                    'tipo' => ['No tienes una caja '.strtolower($tipo).' aperturada para cerrar.'],
                ]);
            }

            $montoCierre = array_key_exists('monto_cierre', $data)
                ? (float) $data['monto_cierre']
                : (float) $open->monto_inicio;
            $ajusteCierre = array_key_exists('ajuste_cierre', $data)
                ? (float) $data['ajuste_cierre']
                : null;

            $open->fill([
                'monto_cierre' => $montoCierre,
                'ajuste_cierre' => $ajusteCierre,
                'observaciones_cierre' => $data['observaciones_cierre'] ?? null,
                'cerrada_at' => Carbon::now($tz),
            ]);
            $open->save();

            $this->audit->log(
                'operational.caja.apertura.close',
                'Cierre de caja',
                'caja_apertura',
                (string) $open->id,
                [
                    'codigo' => $open->codigo,
                    'tipo' => $open->tipo,
                    'cerrada_at' => $open->cerrada_at?->toIso8601String(),
                ],
                'success',
                200
            );

            $this->realtime->entityChanged(
                module: 'caja',
                entity: 'caja_apertura',
                action: 'closed',
                id: (int) $open->id,
                scope: strtolower((string) $open->tipo),
                metadata: ['codigo' => $open->codigo, 'tipo' => $open->tipo],
                actorId: (int) $actor->id,
            );

            return $open->load(['userEntrega', 'userRecepciona', 'areaJefatura']);
        });
    }

    public function resumen(User $actor): array
    {
        $tz = (string) config('app.timezone');
        $ultimo = CajaApertura::query()->orderByDesc('apertura_at')->first();
        $ultimoCierreNormal = CajaApertura::query()
            ->where('tipo', CajaApertura::TIPO_NORMAL)
            ->whereNotNull('cerrada_at')
            ->orderByDesc('cerrada_at')
            ->first();
        $ultimoCierreChica = CajaApertura::query()
            ->where('tipo', CajaApertura::TIPO_CHICA)
            ->whereNotNull('cerrada_at')
            ->orderByDesc('cerrada_at')
            ->first();
        $tipos = CajaApertura::query()
            ->where('user_recepciona_id', $actor->id)
            ->whereNull('cerrada_at')
            ->orderBy('tipo')
            ->pluck('tipo')
            ->map(fn ($t) => strtoupper((string) $t))
            ->unique()
            ->values()
            ->all();

        $hasNormal = in_array(CajaApertura::TIPO_NORMAL, $tipos, true);
        $hasChica = in_array(CajaApertura::TIPO_CHICA, $tipos, true);
        $estadoTexto = match (true) {
            $hasNormal && $hasChica => 'Normal y Chica',
            $hasNormal => 'Normal',
            $hasChica => 'Chica',
            default => 'Sin cajas aperturadas',
        };

        return [
            'ultimo_cierre_monto' => null,
            'ultimo_cierre_moneda' => 'PEN',
            'ultimo_cierre_normal_monto' => $ultimoCierreNormal ? (string) $ultimoCierreNormal->monto_cierre : null,
            'ultimo_cierre_normal_moneda' => $ultimoCierreNormal?->moneda ?? 'PEN',
            'ultimo_cierre_chica_monto' => $ultimoCierreChica ? (string) $ultimoCierreChica->monto_cierre : null,
            'ultimo_cierre_chica_moneda' => $ultimoCierreChica?->moneda ?? 'PEN',
            'fondo_emergencia_monto' => null,
            'fondo_emergencia_moneda' => 'PEN',
            'operadores_activos_text' => $estadoTexto,
            'cajas_activas' => [
                'tipos' => $tipos,
                'normal' => $hasNormal,
                'chica' => $hasChica,
            ],
            'ultima_apertura' => $ultimo ? [
                'codigo' => $ultimo->codigo,
                'monto_inicio' => (string) $ultimo->monto_inicio,
                'moneda' => $ultimo->moneda,
                'apertura_at' => $ultimo->apertura_at?->copy()->setTimezone($tz)->toIso8601String(),
            ] : null,
        ];
    }

    public function aperturaNormalAbierta(User $actor): ?CajaApertura
    {
        return CajaApertura::query()
            ->where('user_recepciona_id', $actor->id)
            ->where('tipo', CajaApertura::TIPO_NORMAL)
            ->whereNull('cerrada_at')
            ->orderByDesc('apertura_at')
            ->first();
    }

    public function requireAperturaNormalAbierta(User $actor): CajaApertura
    {
        $open = $this->aperturaNormalAbierta($actor);
        if (! $open) {
            throw ValidationException::withMessages([
                'caja' => ['Debes aperturar la caja normal antes de emitir o registrar comprobantes.'],
            ]);
        }

        return $open;
    }
}
