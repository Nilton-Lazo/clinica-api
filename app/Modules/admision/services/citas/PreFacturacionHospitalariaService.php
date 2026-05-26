<?php

namespace App\Modules\admision\services\citas;

use App\Core\NroCuentaService;
use App\Core\support\CuentaOrigen;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\PacientePlan;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use App\Modules\caja\support\EmisionComprobanteFacturacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreFacturacionHospitalariaService
{
    public function __construct(
        private NroCuentaService $nroCuentaService,
        private CuentaBitacoraNotaService $bitacoraNotaService,
    ) {}

    public function guardarRegistro(int $pacienteId, int $pacientePlanId, ?string $nroCuentaExistente, array $form): array
    {
        $formNormalizado = $this->normalizarFormParaPersistencia($form);
        $lineas = $formNormalizado['lineas'] ?? [];
        if (! is_array($lineas)) {
            $lineas = [];
        }
        $paquete = $formNormalizado['presupuestoPaquete'] ?? null;
        $tienePaquete = is_array($paquete) && ($paquete['id'] ?? null) !== null;
        if (count($lineas) < 1 && ! $tienePaquete) {
            throw ValidationException::withMessages([
                'form.lineas' => ['Incluye al menos un servicio en la grilla o selecciona un paquete para guardar la pre-facturación hospitalaria.'],
            ]);
        }

        PacientePlan::query()
            ->where('id', $pacientePlanId)
            ->where('paciente_id', $pacienteId)
            ->firstOrFail();

        $paciente = Paciente::query()->findOrFail($pacienteId);
        $nombre = trim((string) ($paciente->nombre_completo ?? ''));
        if ($nombre === '') {
            $nombre = trim(
                trim((string) ($paciente->apellido_paterno ?? '')).' '.
                trim((string) ($paciente->apellido_materno ?? '')).' '.
                trim((string) ($paciente->nombres ?? ''))
            );
        }
        $hc = (string) ($paciente->hc ?? '');
        $nr = $paciente->nr !== null ? (string) $paciente->nr : null;

        $tarifaId = isset($formNormalizado['tarifaId']) && $formNormalizado['tarifaId'] !== null ? (int) $formNormalizado['tarifaId'] : null;
        if ($tarifaId === 0) {
            $tarifaId = null;
        }

        $fechaHospitalizacion = isset($formNormalizado['fechaHospitalizacion']) ? trim((string) $formNormalizado['fechaHospitalizacion']) : '';
        $fechaStr = $fechaHospitalizacion !== '' ? substr($fechaHospitalizacion, 0, 10) : null;

        $bloqueado = ($formNormalizado['bloquearCuenta'] ?? '') === 'BLOQUEADO';
        $estadoCuenta = $bloqueado ? 'CANCELADO_LISTO_PARA_FACTURAR' : 'ACTIVO';

        return DB::transaction(function () use (
            $pacienteId,
            $pacientePlanId,
            $nroCuentaExistente,
            $formNormalizado,
            $nombre,
            $hc,
            $nr,
            $tarifaId,
            $fechaStr,
            $estadoCuenta
        ) {
            $nroTrim = $nroCuentaExistente !== null ? trim($nroCuentaExistente) : '';

            if ($nroTrim !== '') {
                $cuenta = Cuenta::query()->where('nro_cuenta', $nroTrim)->firstOrFail();
                if ($cuenta->origen !== CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value) {
                    throw ValidationException::withMessages(['nro_cuenta' => ['Esta cuenta pertenece a otro flujo y no puede actualizarse desde pre-facturación hospitalaria.']]);
                }
                if ((int) $cuenta->paciente_id !== $pacienteId) {
                    throw ValidationException::withMessages(['paciente_id' => ['El paciente seleccionado no coincide con el paciente asociado a la cuenta.']]);
                }
                $this->assertCuentaEditable($cuenta);

                $registro = PreFacturacionHospitalariaRegistro::query()
                    ->where('id', (int) $cuenta->origen_id)
                    ->firstOrFail();

                $registro->payload = $formNormalizado;
                $registro->save();

                $cuenta->paciente_plan_id = $pacientePlanId;
                $cuenta->tarifa_id = $tarifaId;
                $cuenta->fecha = $fechaStr;
                $cuenta->estado = $estadoCuenta;
                $cuenta->paciente_nombre = $nombre;
                $cuenta->hc = $hc !== '' ? $hc : null;
                $cuenta->nr = $nr;
                $cuenta->save();

                return ['nro_cuenta' => (string) $cuenta->nro_cuenta];
            }

            $nro = $this->nroCuentaService->next();

            $registro = new PreFacturacionHospitalariaRegistro;
            $registro->nro_cuenta = $nro;
            $registro->paciente_id = $pacienteId;
            $registro->payload = $formNormalizado;
            $registro->save();

            $cuenta = new Cuenta;
            $cuenta->nro_cuenta = $nro;
            $cuenta->origen = CuentaOrigen::PRE_FACTURACION_HOSPITALARIA->value;
            $cuenta->origen_id = $registro->id;
            $cuenta->paciente_id = $pacienteId;
            $cuenta->paciente_plan_id = $pacientePlanId;
            $cuenta->tarifa_id = $tarifaId;
            $cuenta->fecha = $fechaStr;
            $cuenta->estado = $estadoCuenta;
            $cuenta->paciente_nombre = $nombre;
            $cuenta->hc = $hc !== '' ? $hc : null;
            $cuenta->nr = $nr;
            $cuenta->save();

            $this->bitacoraNotaService->attachPendingPacienteNotesToCuenta($cuenta);

            return ['nro_cuenta' => $nro];
        });
    }

    private function normalizarFormParaPersistencia(array $form): array
    {
        $normalized = $form;
        $lineas = isset($normalized['lineas']) && is_array($normalized['lineas']) ? $normalized['lineas'] : [];
        $normalized['lineas'] = $this->normalizarLineasConId($lineas);

        return $normalized;
    }

    private function normalizarLineasConId(array $lineas): array
    {
        $maxId = 0;
        foreach ($lineas as $linea) {
            if (! is_array($linea)) {
                continue;
            }
            $id = isset($linea['id']) ? (int) $linea['id'] : 0;
            if ($id > $maxId) {
                $maxId = $id;
            }
        }

        $nextId = max(1, $maxId + 1);
        $result = [];
        foreach ($lineas as $linea) {
            if (! is_array($linea)) {
                continue;
            }
            $lineaId = isset($linea['id']) ? (int) $linea['id'] : 0;
            if ($lineaId <= 0) {
                $linea['id'] = $nextId;
                $nextId++;
            } else {
                $linea['id'] = $lineaId;
            }
            $result[] = $linea;
        }

        return $result;
    }

    private function assertCuentaEditable(Cuenta $cuenta): void
    {
        $estado = strtoupper(trim((string) ($cuenta->estado ?? '')));
        $bloqueada = in_array($estado, ['CANCELADO', 'CANCELADO_LISTO_PARA_FACTURAR'], true);
        $emitida = EmisionComprobanteFacturacion::existeFacturadoraParaCuenta((string) $cuenta->nro_cuenta);

        if (!$bloqueada && !$emitida) {
            return;
        }

        throw ValidationException::withMessages([
            'nro_cuenta' => ['La cuenta de hospitalización ya está cerrada para edición. No se permiten modificaciones.'],
        ]);
    }
}
