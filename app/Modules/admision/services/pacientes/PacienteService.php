<?php

namespace App\Modules\admision\services\pacientes;

use App\Core\audit\AuditService;
use App\Core\support\ParentescoSeguroPaciente;
use App\Core\support\RecordStatus;
use App\Core\support\TipoDocumentoPaciente;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\PacienteContactoEmergencia;
use App\Modules\admision\models\PacientePlan;
use App\Modules\admision\models\TipoCliente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PacienteService
{
    public function __construct(private AuditService $audit) {}

    private function formatNr(int $n): string
    {
        return str_pad((string)$n, 10, '0', STR_PAD_LEFT);
    }

    private function nextNrInt(): int
    {
        $last = Paciente::query()
            ->whereNotNull('nr')
            ->select('nr')
            ->orderByRaw('CAST(nr AS BIGINT) DESC')
            ->value('nr');

        $lastInt = $last !== null ? (int)$last : 0;
        return $lastInt + 1;
    }

    private function resolveDefaultTipoClientes(): array
    {
        $a = TipoCliente::query()
            ->where('system_key', 'DEFAULT_PARTICULAR')
            ->where('estado', RecordStatus::ACTIVO->value)
            ->first();

        $b = TipoCliente::query()
            ->where('system_key', 'DEFAULT_PRIVADO')
            ->where('estado', RecordStatus::ACTIVO->value)
            ->first();

        if (!$a || !$b) {
            throw ValidationException::withMessages([
                'tipos_clientes' => ['Faltan Tipos de cliente default (system_key DEFAULT_PARTICULAR y DEFAULT_PRIVADO). Ejecuta el seeder de system_key.'],
            ]);
        }

        return [$a, $b];
    }

    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = (int)($filters['per_page'] ?? 50);
        $perPage = max(1, min(100, $perPage));

        $q = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $status = isset($filters['status']) ? trim((string)$filters['status']) : null;

        $query = Paciente::query();

        if ($status !== null && $status !== '' && in_array($status, RecordStatus::values(), true)) {
            $query->where('estado', $status);
        }

        if ($q !== null && $q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('numero_documento', 'ilike', "%{$q}%")
                    ->orWhere('nr', 'ilike', "%{$q}%")
                    ->orWhere('nombres', 'ilike', "%{$q}%")
                    ->orWhere('apellido_paterno', 'ilike', "%{$q}%")
                    ->orWhere('apellido_materno', 'ilike', "%{$q}%");
            });
        }

        return $query
        ->orderBy('created_at', 'desc')
        ->orderBy('id', 'desc')
        ->paginate($perPage)
        ->appends(['per_page' => $perPage, 'q' => $q, 'status' => $status]);
    }

    private function fullName(Paciente $p): string
    {
        $x = trim((string)$p->nombre_completo);
        return $x;
    }

    private function upsertContactoEmergencia(Paciente $paciente, ?array $data): void
    {
        if ($data === null) {
            return;
        }

        $exists = PacienteContactoEmergencia::query()->where('paciente_id', $paciente->id)->first();

        $payload = [
            'paciente_id' => (int)$paciente->id,
            'nombres' => $data['nombres'] ?? null,
            'apellido_paterno' => $data['apellido_paterno'] ?? null,
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'parentesco_emergencia' => $data['parentesco_emergencia'] ?? null,
            'celular' => $data['celular'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'estado' => RecordStatus::ACTIVO->value,
        ];

        if ($exists) {
            $exists->fill($payload);
            $exists->save();
        } else {
            PacienteContactoEmergencia::create($payload);
        }
    }

    private function ensureTitularNombre(Paciente $paciente): void
    {
        if ($paciente->parentesco_seguro === ParentescoSeguroPaciente::TITULAR->value) {
            $name = $this->fullName($paciente);
            if ($name !== '') {
                $paciente->titular_nombre = $name;
            }
        }
    }

    private function enforceDocumentoUnico(?Paciente $current, string $tipoDocumento, ?string $numeroDocumento): void
    {
        $tipoDocumento = strtoupper(trim($tipoDocumento));
        $numeroDocumento = $numeroDocumento !== null ? trim($numeroDocumento) : null;

        if ($tipoDocumento === TipoDocumentoPaciente::SIN_DOCUMENTO->value) {
            return;
        }

        if ($numeroDocumento === null || $numeroDocumento === '') {
            return;
        }

        $q = Paciente::query()
            ->where('tipo_documento', $tipoDocumento)
            ->where('numero_documento', $numeroDocumento);

        if ($current) {
            $q->where('id', '<>', $current->id);
        }

        $exists = $q->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'numero_documento' => ['Ya existe un paciente con el mismo tipo y número de documento.'],
            ]);
        }
    }

    public function create(array $data): Paciente
    {
        return DB::transaction(function () use ($data) {
            DB::statement('LOCK TABLE pacientes IN EXCLUSIVE MODE');

            $tipoDoc = strtoupper(trim((string)$data['tipo_documento']));
            $numeroDoc = $data['numero_documento'] ?? null;

            $this->enforceDocumentoUnico(null, $tipoDoc, $numeroDoc);

            $nr = null;
            if ($tipoDoc === TipoDocumentoPaciente::SIN_DOCUMENTO->value) {
                $nr = $this->formatNr($this->nextNrInt());
            }

            $paciente = Paciente::create([
                'tipo_documento' => $tipoDoc,
                'numero_documento' => $numeroDoc !== null && trim((string)$numeroDoc) !== '' ? trim((string)$numeroDoc) : null,
                'nr' => $nr,

                'nombres' => $data['nombres'] ?? null,
                'apellido_paterno' => $data['apellido_paterno'] ?? null,
                'apellido_materno' => $data['apellido_materno'] ?? null,

                'estado_civil' => $data['estado_civil'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,

                'nacionalidad_iso2' => $data['nacionalidad_iso2'] ?? null,
                'ubigeo_nacimiento' => $data['ubigeo_nacimiento'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'ubigeo_domicilio' => $data['ubigeo_domicilio'] ?? null,

                'parentesco_seguro' => $data['parentesco_seguro'] ?? ParentescoSeguroPaciente::NO_DEFINIDO->value,
                'titular_nombre' => $data['titular_nombre'] ?? null,

                'celular' => $data['celular'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,

                'medico_tratante_id' => $data['medico_tratante_id'] ?? null,
                'tipo_sangre' => $data['tipo_sangre'] ?? null,
                'tipo_paciente' => $data['tipo_paciente'] ?? null,

                'ocupacion' => $data['ocupacion'] ?? null,

                'medio_informacion' => $data['medio_informacion'] ?? null,
                'medio_informacion_detalle' => $data['medio_informacion_detalle'] ?? null,

                'ubicacion_archivo_hc' => $data['ubicacion_archivo_hc'] ?? null,

                'estado' => $data['estado'] ?? RecordStatus::ACTIVO->value,
            ]);

            $this->ensureTitularNombre($paciente);
            $paciente->save();

            $this->upsertContactoEmergencia($paciente, $data['contacto_emergencia'] ?? null);

            [$tcA, $tcB] = $this->resolveDefaultTipoClientes();

            $today = now()->toDateString();
            $cond = $paciente->parentesco_seguro ?? ParentescoSeguroPaciente::NO_DEFINIDO->value;

            PacientePlan::create([
                'paciente_id' => (int)$paciente->id,
                'tipo_cliente_id' => (int)$tcA->id,
                'parentesco_seguro' => $cond,
                'fecha_afiliacion' => $today,
                'estado' => RecordStatus::ACTIVO->value,
            ]);

            PacientePlan::create([
                'paciente_id' => (int)$paciente->id,
                'tipo_cliente_id' => (int)$tcB->id,
                'parentesco_seguro' => $cond,
                'fecha_afiliacion' => $today,
                'estado' => RecordStatus::ACTIVO->value,
            ]);

            $this->audit->log(
                'admision.pacientes.create',
                'Crear paciente',
                'paciente',
                (string)$paciente->id,
                [
                    'tipo_documento' => $paciente->tipo_documento,
                    'numero_documento' => $paciente->numero_documento,
                    'nr' => $paciente->nr,
                    'estado' => $paciente->estado,
                ],
                'success',
                201
            );

            return $this->loadFull($paciente);
        });
    }

    public function update(Paciente $paciente, array $data): Paciente
    {
        return DB::transaction(function () use ($paciente, $data) {
            $before = $paciente->only([
                'tipo_documento',
                'numero_documento',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'parentesco_seguro',
                'titular_nombre',
                'estado',
            ]);

            $tipoDoc = strtoupper(trim((string)$data['tipo_documento']));
            $numeroDoc = $data['numero_documento'] ?? null;

            $this->enforceDocumentoUnico($paciente, $tipoDoc, $numeroDoc);

            $nr = $paciente->nr;
            if ($tipoDoc === TipoDocumentoPaciente::SIN_DOCUMENTO->value && ($nr === null || trim((string)$nr) === '')) {
                DB::statement('LOCK TABLE pacientes IN EXCLUSIVE MODE');
                $nr = $this->formatNr($this->nextNrInt());
            }

            $oldCond = $paciente->parentesco_seguro;

            $paciente->fill([
                'tipo_documento' => $tipoDoc,
                'numero_documento' => $numeroDoc !== null && trim((string)$numeroDoc) !== '' ? trim((string)$numeroDoc) : null,
                'nr' => $nr,

                'nombres' => $data['nombres'] ?? null,
                'apellido_paterno' => $data['apellido_paterno'] ?? null,
                'apellido_materno' => $data['apellido_materno'] ?? null,

                'estado_civil' => $data['estado_civil'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,

                'nacionalidad_iso2' => $data['nacionalidad_iso2'] ?? null,
                'ubigeo_nacimiento' => $data['ubigeo_nacimiento'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'ubigeo_domicilio' => $data['ubigeo_domicilio'] ?? null,

                'parentesco_seguro' => $data['parentesco_seguro'] ?? null,
                'titular_nombre' => $data['titular_nombre'] ?? null,

                'celular' => $data['celular'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'email' => $data['email'] ?? null,

                'medico_tratante_id' => $data['medico_tratante_id'] ?? null,
                'tipo_sangre' => $data['tipo_sangre'] ?? null,
                'tipo_paciente' => $data['tipo_paciente'] ?? null,

                'ocupacion' => $data['ocupacion'] ?? null,

                'medio_informacion' => $data['medio_informacion'] ?? null,
                'medio_informacion_detalle' => $data['medio_informacion_detalle'] ?? null,

                'ubicacion_archivo_hc' => $data['ubicacion_archivo_hc'] ?? null,

                'estado' => $data['estado'],
            ]);

            if ($paciente->parentesco_seguro === null || trim((string)$paciente->parentesco_seguro) === '') {
                $paciente->parentesco_seguro = ParentescoSeguroPaciente::NO_DEFINIDO->value;
            }

            $this->ensureTitularNombre($paciente);

            $paciente->save();

            if ($oldCond !== $paciente->parentesco_seguro) {
                PacientePlan::query()
                    ->where('paciente_id', $paciente->id)
                    ->update([
                        'parentesco_seguro' => $paciente->parentesco_seguro,
                        'updated_at' => now(),
                    ]);
            }

            $this->upsertContactoEmergencia($paciente, $data['contacto_emergencia'] ?? null);

            $after = $paciente->only([
                'tipo_documento',
                'numero_documento',
                'nr',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'parentesco_seguro',
                'titular_nombre',
                'estado',
            ]);

            $this->audit->log(
                'admision.pacientes.update',
                'Actualizar paciente',
                'paciente',
                (string)$paciente->id,
                ['before' => $before, 'after' => $after],
                'success',
                200
            );

            return $this->loadFull($paciente);
        });
    }

    public function deactivate(Paciente $paciente): Paciente
    {
        return DB::transaction(function () use ($paciente) {
            $before = $paciente->only(['estado']);

            $paciente->estado = RecordStatus::INACTIVO->value;
            $paciente->save();

            PacientePlan::query()
                ->where('paciente_id', $paciente->id)
                ->update(['estado' => RecordStatus::INACTIVO->value, 'updated_at' => now()]);

            $this->audit->log(
                'admision.pacientes.deactivate',
                'Desactivar paciente',
                'paciente',
                (string)$paciente->id,
                ['before' => $before, 'after' => $paciente->only(['estado'])],
                'success',
                200
            );

            return $this->loadFull($paciente);
        });
    }

    public function addPlan(Paciente $paciente, array $data): PacientePlan
    {
        return DB::transaction(function () use ($paciente, $data) {
            $tipoClienteId = (int)$data['tipo_cliente_id'];

            $tc = TipoCliente::query()
                ->where('id', $tipoClienteId)
                ->where('estado', RecordStatus::ACTIVO->value)
                ->first();

            if (!$tc) {
                throw ValidationException::withMessages(['tipo_cliente_id' => ['Tipo de cliente no existe o no está ACTIVO.']]);
            }

            $exists = PacientePlan::query()
                ->where('paciente_id', $paciente->id)
                ->where('tipo_cliente_id', $tipoClienteId)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['tipo_cliente_id' => ['El paciente ya tiene este plan registrado.']]);
            }

            $fecha = $data['fecha_afiliacion'] ?? null;
            $fecha = $fecha !== null && trim((string)$fecha) !== '' ? $fecha : now()->toDateString();
            $estado = $data['estado'] ?? RecordStatus::ACTIVO->value;

            $plan = PacientePlan::create([
                'paciente_id' => (int)$paciente->id,
                'tipo_cliente_id' => $tipoClienteId,
                'parentesco_seguro' => $paciente->parentesco_seguro ?? ParentescoSeguroPaciente::NO_DEFINIDO->value,
                'fecha_afiliacion' => $fecha,
                'estado' => $estado,
            ]);

            $this->audit->log(
                'admision.pacientes.planes.add',
                'Agregar plan al paciente',
                'paciente_plan',
                (string)$plan->id,
                [
                    'paciente_id' => (int)$paciente->id,
                    'tipo_cliente_id' => $tipoClienteId,
                    'fecha_afiliacion' => $plan->fecha_afiliacion,
                ],
                'success',
                201
            );

            return $plan->load(['tipoCliente']);
        });
    }

    public function updatePlan(Paciente $paciente, PacientePlan $plan, array $data): PacientePlan
    {
        return DB::transaction(function () use ($paciente, $plan, $data) {
            $tipoClienteId = (int)$data['tipo_cliente_id'];

            $tc = TipoCliente::query()
                ->where('id', $tipoClienteId)
                ->where('estado', RecordStatus::ACTIVO->value)
                ->first();

            if (!$tc) {
                throw ValidationException::withMessages(['tipo_cliente_id' => ['Tipo de cliente no existe o no está ACTIVO.']]);
            }

            $exists = PacientePlan::query()
                ->where('paciente_id', $paciente->id)
                ->where('tipo_cliente_id', $tipoClienteId)
                ->where('id', '<>', $plan->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages(['tipo_cliente_id' => ['El paciente ya tiene este plan registrado.']]);
            }

            $fecha = $data['fecha_afiliacion'] ?? null;
            if ($fecha !== null && trim((string)$fecha) === '') {
                $fecha = null;
            }

            $before = $plan->only(['tipo_cliente_id', 'fecha_afiliacion', 'estado']);

            $plan->fill([
                'tipo_cliente_id' => $tipoClienteId,
                'parentesco_seguro' => $paciente->parentesco_seguro ?? ParentescoSeguroPaciente::NO_DEFINIDO->value,
                'fecha_afiliacion' => $fecha ?? $plan->fecha_afiliacion ?? now()->toDateString(),
                'estado' => $data['estado'],
            ]);

            $plan->save();

            $this->audit->log(
                'admision.pacientes.planes.update',
                'Actualizar plan del paciente',
                'paciente_plan',
                (string)$plan->id,
                ['before' => $before, 'after' => $plan->only(['tipo_cliente_id', 'fecha_afiliacion', 'estado'])],
                'success',
                200
            );

            return $plan->load(['tipoCliente']);
        });
    }

    public function deactivatePlan(PacientePlan $plan): PacientePlan
    {
        return DB::transaction(function () use ($plan) {
            $before = $plan->only(['estado']);

            $plan->estado = RecordStatus::INACTIVO->value;
            $plan->save();

            $this->audit->log(
                'admision.pacientes.planes.deactivate',
                'Desactivar plan del paciente',
                'paciente_plan',
                (string)$plan->id,
                ['before' => $before, 'after' => $plan->only(['estado'])],
                'success',
                200
            );

            return $plan->load(['tipoCliente']);
        });
    }

    public function loadFull(Paciente $paciente): Paciente
    {
        return $paciente->load([
            'paisNacionalidad',
            'contactoEmergencia',
            'planes.tipoCliente.iafa',
        ]);
    }
}
