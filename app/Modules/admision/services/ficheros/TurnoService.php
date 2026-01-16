<?php

namespace App\Modules\admision\services\ficheros;

use App\Core\audit\AuditService;
use App\Core\support\RecordStatus;
use App\Modules\admision\models\Turno;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TurnoService
{
    public function __construct(private AuditService $audit) {}

    private function formatCodigo(int $n): string
    {
        $codigo = str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        if (strlen($codigo) !== 3) {
            throw new \RuntimeException('No se pudo generar el código.');
        }
        return $codigo;
    }

    public function previewNextCodigo(): string
    {
        $last = Turno::query()->orderByDesc('codigo')->value('codigo');
        $next = $last ? ((int)$last + 1) : 1;

        if ($next > 999) {
            return $this->formatCodigo(999);
        }

        return $this->formatCodigo($next);
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $query = Turno::query();

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'ilike', "%{$q}%")
                    ->orWhere('descripcion', 'ilike', "%{$q}%");
            });
        }

        return $query->orderBy('codigo')->paginate($perPage)->appends([
            'per_page' => $perPage,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function create(array $data): Turno
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE turnos IN EXCLUSIVE MODE');

            $last = Turno::query()->orderByDesc('codigo')->value('codigo');
            $next = $last ? ((int)$last + 1) : 1;

            if ($next > 999) {
                throw ValidationException::withMessages(['codigo' => ['Se alcanzó el máximo de turnos (999).']]);
            }

            $codigo = $this->formatCodigo($next);

            [$duracionMin, $hi, $hf] = $this->calcDurationMinutes($data['hora_inicio'], $data['hora_fin']);
            $auto = $this->buildDescripcion($codigo, $hi, $hf);

            $descIn = isset($data['descripcion']) ? trim((string)$data['descripcion']) : '';
            $descripcion = $descIn !== '' ? $descIn : $auto;

            $turno = Turno::create([
                'codigo' => $codigo,
                'hora_inicio' => $hi,
                'hora_fin' => $hf,
                'duracion_minutos' => $duracionMin,
                'descripcion' => $descripcion,
                'tipo_turno' => $data['tipo_turno'],
                'jornada' => $data['jornada'],
                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'masterdata.admision.turnos.create',
                'Crear turno',
                'turno',
                (string)$turno->id,
                $turno->only([
                    'codigo',
                    'hora_inicio',
                    'hora_fin',
                    'duracion_minutos',
                    'descripcion',
                    'tipo_turno',
                    'jornada',
                    'estado',
                ]),
                'success',
                201
            );

            return $turno;
        });
    }

    public function update(Turno $turno, array $data): Turno
    {
        return DB::transaction(function () use ($turno, $data) {
            $before = $turno->only([
                'hora_inicio',
                'hora_fin',
                'duracion_minutos',
                'descripcion',
                'tipo_turno',
                'jornada',
                'estado',
            ]);

            [$duracionMin, $hi, $hf] = $this->calcDurationMinutes($data['hora_inicio'], $data['hora_fin']);
            $auto = $this->buildDescripcion($turno->codigo, $hi, $hf);

            $attrs = [
                'hora_inicio' => $hi,
                'hora_fin' => $hf,
                'duracion_minutos' => $duracionMin,
                'tipo_turno' => $data['tipo_turno'],
                'jornada' => $data['jornada'],
                'estado' => $data['estado'],
            ];

            if (array_key_exists('descripcion', $data)) {
                $descIn = $data['descripcion'] === null ? '' : trim((string)$data['descripcion']);
                $attrs['descripcion'] = $descIn !== '' ? $descIn : $auto;
            }

            $turno->fill($attrs);
            $turno->save();

            $after = $turno->only([
                'hora_inicio',
                'hora_fin',
                'duracion_minutos',
                'descripcion',
                'tipo_turno',
                'jornada',
                'estado',
            ]);

            $this->audit->log(
                'masterdata.admision.turnos.update',
                'Actualizar turno',
                'turno',
                (string)$turno->id,
                [
                    'before' => $before,
                    'after' => $after,
                ],
                'success',
                200
            );

            return $turno;
        });
    }

    public function deactivate(Turno $turno): Turno
    {
        return DB::transaction(function () use ($turno) {
            $before = $turno->only(['estado']);

            $turno->estado = RecordStatus::INACTIVO->value;
            $turno->save();

            $this->audit->log(
                'masterdata.admision.turnos.deactivate',
                'Desactivar turno',
                'turno',
                (string)$turno->id,
                [
                    'before' => $before,
                    'after' => $turno->only(['estado']),
                ],
                'success',
                200
            );

            return $turno;
        });
    }

    private function calcDurationMinutes(string $horaInicio, string $horaFin): array
    {
        $hi = Carbon::createFromFormat('H:i', $horaInicio);
        $hf = Carbon::createFromFormat('H:i', $horaFin);

        if ($hf->lessThanOrEqualTo($hi)) {
            $hf = $hf->copy()->addDay();
        }

        $min = $hi->diffInMinutes($hf);

        if ($min <= 0 || $min > (24 * 60)) {
            throw ValidationException::withMessages(['hora_fin' => ['Rango de horas inválido.']]);
        }

        return [$min, $hi->format('H:i'), $hf->format('H:i')];
    }

    private function buildDescripcion(string $codigo, string $horaInicio, string $horaFin): string
    {
        return "Turno: {$codigo} - de {$horaInicio} a {$horaFin}";
    }
}
