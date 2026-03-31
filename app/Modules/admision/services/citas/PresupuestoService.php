<?php

namespace App\Modules\admision\services\citas;

use App\Core\audit\AuditService;
use App\Modules\admision\models\PacientePlan;
use App\Modules\admision\models\Presupuesto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use JsonException;
use Illuminate\Validation\ValidationException;

class PresupuestoService
{
    private const CODIGO_MIN_DIGITS = 10;

    /** Caché de vista previa del siguiente código (se invalida al crear un presupuesto). */
    private const NEXT_CODIGO_CACHE_KEY = 'admision:presupuestos:next_codigo_preview';

    private const NEXT_CODIGO_CACHE_TTL_SECONDS = 3600;

    public function __construct(private AuditService $audit) {}

    /**
     * Código legible alineado al id: mínimo 10 dígitos con ceros a la izquierda; si el id supera ese ancho, no se trunca.
     */
    public function formatCodigoFromId(int $id): string
    {
        $s = (string) $id;
        $len = max(self::CODIGO_MIN_DIGITS, strlen($s));

        return str_pad($s, $len, '0', STR_PAD_LEFT);
    }

    /**
     * Vista previa del siguiente código. Usa caché (invalidada al guardar) para responder rápido;
     * el código definitivo sigue siendo único (derivado del id autoincremental tras el INSERT).
     */
    public function previewNextCodigo(): string
    {
        return Cache::remember(
            self::NEXT_CODIGO_CACHE_KEY,
            self::NEXT_CODIGO_CACHE_TTL_SECONDS,
            function (): string {
                $maxId = (int) Presupuesto::query()->max('id');

                return $this->formatCodigoFromId($maxId + 1);
            }
        );
    }

    /**
     * PostgreSQL / PDO requieren JSON como cadena en el INSERT. Se codifica aquí de forma explícita.
     *
     * @param  array<string, mixed>  $payload
     */
    /**
     * Listado paginado con datos del paciente (HC / nombre alineados a la lógica del modelo Paciente).
     *
     * @param  array<string, mixed>  $filters  validated index request
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 50)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $driver = DB::connection()->getDriverName();
        $hcExpr = match ($driver) {
            'pgsql' => "CASE WHEN NULLIF(TRIM(pacientes.numero_documento::text), '') IS NOT NULL THEN TRIM(pacientes.numero_documento::text) ELSE COALESCE(pacientes.nr::text, '') END",
            default => "CASE WHEN NULLIF(TRIM(pacientes.numero_documento), '') IS NOT NULL THEN TRIM(pacientes.numero_documento) ELSE COALESCE(pacientes.nr, '') END",
        };
        $nombreExpr = "TRIM(CONCAT_WS(' ', pacientes.apellido_paterno, pacientes.apellido_materno, pacientes.nombres))";

        $query = DB::table('admision_presupuestos')
            ->join('pacientes', 'pacientes.id', '=', 'admision_presupuestos.paciente_id')
            ->leftJoin('paciente_planes as pp', 'pp.id', '=', 'admision_presupuestos.paciente_plan_id')
            ->leftJoin('tipos_clientes as tc', 'tc.id', '=', 'pp.tipo_cliente_id')
            ->select([
                'admision_presupuestos.id',
                'admision_presupuestos.codigo',
                'admision_presupuestos.vigencia_hasta',
                'admision_presupuestos.estado',
                'admision_presupuestos.created_at',
                'pacientes.nr as paciente_nr',
                'tc.descripcion_tipo_cliente as plan_descripcion',
            ])
            ->selectRaw("{$hcExpr} as hc")
            ->selectRaw("{$nombreExpr} as nombre_completo");

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $escaped = addcslashes($q, '%_\\');
            $term = '%'.$escaped.'%';
            $query->where(function ($sub) use ($term, $hcExpr, $nombreExpr, $driver) {
                $sub->where('admision_presupuestos.codigo', 'like', $term)
                    ->orWhere('pacientes.numero_documento', 'like', $term)
                    ->orWhere('pacientes.nr', 'like', $term);
                if ($driver === 'pgsql') {
                    $sub->orWhereRaw("({$nombreExpr}) ilike ?", [$term])
                        ->orWhereRaw("({$hcExpr}) ilike ?", [$term]);
                } else {
                    $sub->orWhereRaw("LOWER({$nombreExpr}) like LOWER(?)", [$term])
                        ->orWhereRaw("LOWER({$hcExpr}) like LOWER(?)", [$term]);
                }
            });
        }

        if (! empty($filters['vigencia_desde'])) {
            $query->whereDate('admision_presupuestos.vigencia_hasta', '>=', $filters['vigencia_desde']);
        }
        if (! empty($filters['vigencia_hasta'])) {
            $query->whereDate('admision_presupuestos.vigencia_hasta', '<=', $filters['vigencia_hasta']);
        }
        if (! empty($filters['estado']) && is_string($filters['estado'])) {
            $query->where('admision_presupuestos.estado', $filters['estado']);
        }

        $query->orderByDesc('admision_presupuestos.created_at');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($row) {
            $v = $row->vigencia_hasta ?? null;
            $vigencia = $v ? (string) $v : null;
            if ($vigencia && strlen($vigencia) > 10) {
                $vigencia = substr($vigencia, 0, 10);
            }

            $planLabel = trim((string) ($row->plan_descripcion ?? ''));

            return [
                'id' => (int) $row->id,
                'codigo' => (string) ($row->codigo ?? ''),
                'hc' => (string) ($row->hc ?? ''),
                'nr' => $row->paciente_nr !== null && $row->paciente_nr !== '' ? (string) $row->paciente_nr : null,
                'nombre_completo' => trim((string) ($row->nombre_completo ?? '')) ?: null,
                'plan' => $planLabel !== '' ? $planLabel : null,
                'vigencia_hasta' => $vigencia,
                'estado' => (string) ($row->estado ?? ''),
                'created_at' => $row->created_at ? (string) $row->created_at : null,
            ];
        });

        return $paginator;
    }

    private function encodePayloadForStorage(array $payload): string
    {
        try {
            return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw ValidationException::withMessages([
                'payload' => ['El contenido del presupuesto no se pudo serializar (JSON inválido).'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data  validated request data
     * @return array{presupuesto: Presupuesto}
     */
    public function store(array $data, ?int $userId): array
    {
        $pacienteId = (int) $data['paciente_id'];
        $planId = (int) $data['paciente_plan_id'];

        $plan = PacientePlan::query()
            ->whereKey($planId)
            ->where('paciente_id', $pacienteId)
            ->first();

        if (!$plan) {
            throw ValidationException::withMessages([
                'paciente_plan_id' => ['El plan no pertenece al paciente indicado.'],
            ]);
        }

        $result = DB::transaction(function () use ($data, $userId) {
            $presupuesto = Presupuesto::query()->create([
                'paciente_id' => (int) $data['paciente_id'],
                'paciente_plan_id' => (int) $data['paciente_plan_id'],
                'tarifa_id' => isset($data['tarifa_id']) ? (int) $data['tarifa_id'] : null,
                'cliente_id' => isset($data['cliente_id']) ? (int) $data['cliente_id'] : null,
                'vigencia_hasta' => $data['vigencia_hasta'],
                'estado' => $data['estado'],
                'monto_a_pagar' => $data['monto_a_pagar'],
                'payload' => $this->encodePayloadForStorage($data['payload']),
                'created_by_user_id' => $userId,
            ]);

            $codigo = $this->formatCodigoFromId($presupuesto->id);
            $presupuesto->codigo = $codigo;
            $presupuesto->save();

            $this->audit->log(
                'admision.presupuestos.store',
                'Crear presupuesto de admisión',
                'presupuesto',
                (string) $presupuesto->id,
                [
                    'codigo' => $codigo,
                    'paciente_id' => $presupuesto->paciente_id,
                    'paciente_plan_id' => $presupuesto->paciente_plan_id,
                    'estado' => $presupuesto->estado,
                    'monto_a_pagar' => (string) $presupuesto->monto_a_pagar,
                ],
                'success',
                201
            );

            return ['presupuesto' => $presupuesto];
        });

        Cache::forget(self::NEXT_CODIGO_CACHE_KEY);

        return $result;
    }
}
