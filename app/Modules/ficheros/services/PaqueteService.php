<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Paquete;
use App\Modules\admision\models\Tarifa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaqueteService
{
    use AppliesListingQuery;

    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigoInt(): int
    {
        $last = Paquete::query()
            ->select('codigo')
            ->orderByRaw('CAST(codigo AS INTEGER) DESC')
            ->value('codigo');

        $lastInt = $last !== null ? (int) $last : 0;

        return $lastInt + 1;
    }

    public function previewNextCodigo(): string
    {
        return $this->formatCodigo($this->nextCodigoInt());
    }

    private function loadTarifaOperativa(int $tarifaId): Tarifa
    {
        $tarifa = Tarifa::query()->find($tarifaId);

        if (! $tarifa) {
            throw ValidationException::withMessages(['tarifa_id' => ['La tarifa seleccionada no existe o ya no está disponible.']]);
        }

        if ($tarifa->estado !== RecordStatus::ACTIVO->value) {
            throw ValidationException::withMessages(['tarifa_id' => ['La tarifa seleccionada debe estar activa para registrar paquetes.']]);
        }

        if ($tarifa->tarifa_base) {
            throw ValidationException::withMessages(['tarifa_id' => ['Selecciona una tarifa operativa; la tarifa base no permite registrar paquetes.']]);
        }

        return $tarifa;
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;

    private const CACHE_VERSION_KEY = 'ficheros:paquetes:version';

    private function getListCacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_VERSION_KEY, 0);
    }

    private function invalidateListCache(): void
    {
        Cache::put(self::CACHE_VERSION_KEY, $this->getListCacheVersion() + 1, 86400);
    }

    public function paginate(GridParams $params): LengthAwarePaginator
    {
        $version = $this->getListCacheVersion();
        $cacheKey = 'ficheros:paquetes:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Paquete::query()->with([
                'tarifa:id,codigo,descripcion_tarifa,estado,tarifa_base',
            ]);
            $this->applyListingStatus($query, $params);

            if ($params->q !== null) {
                $term = $params->q;
                $query->where(function ($sub) use ($term) {
                    $sub->where('codigo', 'ilike', "%{$term}%")
                        ->orWhere('descripcion', 'ilike', "%{$term}%")
                        ->orWhere('cuenta_contabilidad', 'ilike', "%{$term}%")
                        ->orWhereHas('tarifa', function ($t) use ($term) {
                            $t->where('codigo', 'ilike', "%{$term}%")
                                ->orWhere('descripcion_tarifa', 'ilike', "%{$term}%");
                        });
                });
            }

            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Paquete
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE paquetes IN EXCLUSIVE MODE');

            $codigo = $this->formatCodigo($this->nextCodigoInt());

            $this->loadTarifaOperativa((int) $data['tarifa_id']);

            $paquete = Paquete::create([
                'codigo' => $codigo,
                'descripcion' => $data['descripcion'],
                'tarifa_id' => (int) $data['tarifa_id'],
                'precio_sin_igv' => $data['precio_sin_igv'],
                'vigencia_actual' => $data['vigencia_actual'],
                'dias_hospitalizacion' => array_key_exists('dias_hospitalizacion', $data) ? $data['dias_hospitalizacion'] : null,
                'cuenta_contabilidad' => $data['cuenta_contabilidad'] ?? null,
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $paquete->load(['tarifa:id,codigo,descripcion_tarifa,estado,tarifa_base']);

            $this->audit->log(
                'masterdata.ficheros.paquetes.create',
                'Crear paquete',
                'paquete',
                (string) $paquete->id,
                $paquete->only([
                    'codigo',
                    'descripcion',
                    'tarifa_id',
                    'precio_sin_igv',
                    'vigencia_actual',
                    'dias_hospitalizacion',
                    'cuenta_contabilidad',
                    'estado',
                ]),
                'success',
                201
            );

            $this->invalidateListCache();

            return $paquete;
        });
    }

    public function update(Paquete $paquete, array $data): Paquete
    {
        return DB::transaction(function () use ($paquete, $data) {
            $before = $paquete->only([
                'descripcion',
                'tarifa_id',
                'precio_sin_igv',
                'vigencia_actual',
                'dias_hospitalizacion',
                'cuenta_contabilidad',
                'estado',
            ]);

            $this->loadTarifaOperativa((int) $data['tarifa_id']);

            $paquete->fill([
                'descripcion' => $data['descripcion'],
                'tarifa_id' => (int) $data['tarifa_id'],
                'precio_sin_igv' => $data['precio_sin_igv'],
                'vigencia_actual' => $data['vigencia_actual'],
                'dias_hospitalizacion' => array_key_exists('dias_hospitalizacion', $data) ? $data['dias_hospitalizacion'] : null,
                'cuenta_contabilidad' => $data['cuenta_contabilidad'] ?? null,
                'estado' => $data['estado'],
            ]);
            $paquete->save();

            $paquete->load(['tarifa:id,codigo,descripcion_tarifa,estado,tarifa_base']);

            $after = $paquete->only([
                'descripcion',
                'tarifa_id',
                'precio_sin_igv',
                'vigencia_actual',
                'dias_hospitalizacion',
                'cuenta_contabilidad',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.ficheros.paquetes.update',
                'Actualizar paquete',
                'paquete',
                (string) $paquete->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $paquete;
        });
    }

    public function deactivate(Paquete $paquete): Paquete
    {
        return DB::transaction(function () use ($paquete) {
            $before = $paquete->only(['estado']);

            $paquete->estado = RecordStatus::INACTIVO->value;
            $paquete->save();

            $paquete->load(['tarifa:id,codigo,descripcion_tarifa,estado,tarifa_base']);

            $this->audit->log(
                'masterdata.ficheros.paquetes.deactivate',
                'Desactivar paquete',
                'paquete',
                (string) $paquete->id,
                ['before' => $before, 'after' => $paquete->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $paquete;
        });
    }
}
