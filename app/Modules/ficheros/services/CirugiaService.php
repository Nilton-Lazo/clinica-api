<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Cirugia;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CirugiaService
{
    use AppliesListingQuery;

    public function __construct(
        private AuditService $audit,
    ) {}

    private function formatCodigo(int $n): string
    {
        return CodigoCorrelativo::format($n);
    }

    private function nextCodigo(): string
    {
        $row = DB::selectOne("SELECT nextval('cirugias_codigo_seq') AS n");
        $n = (int) ($row->n ?? 0);

        if ($n <= 0) {
            throw new \RuntimeException('No se pudo generar el código de cirugía.');
        }

        return $this->formatCodigo($n);
    }

    public function peekNextCodigo(): string
    {
        $last = Cirugia::query()
            ->select('codigo')
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw('codigo::int desc')
            ->first();

        $n = 0;
        if ($last && is_string($last->codigo) && $last->codigo !== '') {
            $n = (int) $last->codigo;
        }

        return $this->formatCodigo($n + 1);
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;

    private const CACHE_VERSION_KEY = 'ficheros:cirugias:version';

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
        $cacheKey = 'ficheros:cirugias:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Cirugia::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Cirugia
    {
        return DB::transaction(function () use ($data) {
            $cirugia = Cirugia::create([
                'codigo' => $this->nextCodigo(),
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.ficheros.cirugias.create',
                'Crear cirugía',
                'cirugia',
                (string) $cirugia->id,
                [
                    'codigo' => $cirugia->codigo,
                    'descripcion' => $cirugia->descripcion,
                    'estado' => $cirugia->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $cirugia;
        });
    }

    public function update(Cirugia $cirugia, array $data): Cirugia
    {
        return DB::transaction(function () use ($cirugia, $data) {
            $before = $cirugia->only(['codigo', 'descripcion', 'estado']);

            $cirugia->fill([
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);

            $cirugia->save();

            $after = $cirugia->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.cirugias.update',
                'Actualizar cirugía',
                'cirugia',
                (string) $cirugia->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            $this->invalidateListCache();

            return $cirugia;
        });
    }

    public function deactivate(Cirugia $cirugia): Cirugia
    {
        return DB::transaction(function () use ($cirugia) {
            $before = $cirugia->only(['estado']);

            $cirugia->estado = RecordStatus::INACTIVO->value;
            $cirugia->save();

            $this->audit->log(
                'masterdata.ficheros.cirugias.deactivate',
                'Desactivar cirugía',
                'cirugia',
                (string) $cirugia->id,
                [
                    'before' => $before,
                    'after' => $cirugia->only(['estado']),
                ],
                'success',
                200
            );

            $this->invalidateListCache();

            return $cirugia;
        });
    }
}
