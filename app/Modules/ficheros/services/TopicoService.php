<?php

namespace App\Modules\ficheros\services;

use App\Core\audit\AuditService;
use App\Core\grid\Concerns\AppliesListingQuery;
use App\Core\grid\GridParams;
use App\Core\support\RecordStatus;
use App\Core\support\CodigoCorrelativo;
use App\Modules\admision\models\Topico;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopicoService
{
    use AppliesListingQuery;

    public function __construct(
        private AuditService $audit,
    ) {}

    private function formatCodigo(int $n): string
    {
        $codigo = CodigoCorrelativo::format($n);
        CodigoCorrelativo::guardMaxLength($codigo);

        return $codigo;
    }

    public function peekNextCodigo(): string
    {
        $last = Topico::query()
            ->select('codigo')
            ->whereRaw("codigo ~ '^[0-9]+$'")
            ->orderByRaw("codigo::int desc")
            ->first();

        $n = 0;
        if ($last && is_string($last->codigo) && $last->codigo !== '') {
            $n = (int) $last->codigo;
        }
        return $this->formatCodigo($n + 1);
    }

    private const INDEX_CACHE_TTL_SECONDS = 30;
    private const CACHE_VERSION_KEY = 'ficheros:parametros:emergencia:topico:version';

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
        $cacheKey = 'ficheros:parametros:emergencia:topico:index:' . $version . ':' . $params->toCacheKey('v1');

        return Cache::remember($cacheKey, self::INDEX_CACHE_TTL_SECONDS, function () use ($params) {
            $query = Topico::query();
            $this->applyListingStatus($query, $params);
            $this->applyListingSearch($query, $params, ['codigo', 'descripcion']);
            $this->applyListingSort($query, $params, ['codigo', 'descripcion', 'estado'], 'codigo');

            return $query->paginate($params->perPage, ['*'], 'page', $params->page);
        });
    }

    public function create(array $data): Topico
    {
        return DB::transaction(function () use ($data) {
            $codigo = isset($data['codigo']) && trim((string) $data['codigo']) !== ''
            ? trim((string) $data['codigo'])
            : $this->peekNextCodigo();
        $topico = Topico::create([
            'codigo' => $codigo,
            'descripcion' => $data['descripcion'],
            'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
        ]);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.topico.create',
                'Crear tópico',
                'topico',
                (string) $topico->id,
                [
                    'codigo' => $topico->codigo,
                    'descripcion' => $topico->descripcion,
                    'estado' => $topico->estado,
                ],
                'success',
                201
            );

            $this->invalidateListCache();

            return $topico;
        });
    }

    public function update(Topico $topico, array $data): Topico
    {
        return DB::transaction(function () use ($topico, $data) {
            $before = $topico->only(['codigo', 'descripcion', 'estado']);

            $topico->fill([
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'estado' => $data['estado'],
            ]);
            $topico->save();

            $after = $topico->only(['codigo', 'descripcion', 'estado']);

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.topico.update',
                'Actualizar tópico',
                'topico',
                (string) $topico->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            $this->invalidateListCache();

            return $topico;
        });
    }

    public function deactivate(Topico $topico): Topico
    {
        return DB::transaction(function () use ($topico) {
            $before = $topico->only(['estado']);
            $topico->estado = RecordStatus::INACTIVO->value;
            $topico->save();

            $this->audit->log(
                'masterdata.ficheros.parametros.emergencia.topico.deactivate',
                'Desactivar tópico',
                'topico',
                (string) $topico->id,
                ['before' => $before, 'after' => $topico->only(['estado'])],
                'success',
                200
            );

            $this->invalidateListCache();

            return $topico;
        });
    }
}
