<?php

namespace App\Modules\admision\services\citas;

use App\Core\NroCuentaService;
use App\Core\support\CuentaOrigen;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\PacientePlan;
use App\Modules\admision\models\PreFacturacionHospitalariaRegistro;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreFacturacionHospitalariaService
{
    public function __construct(
        private NroCuentaService $nroCuentaService,
        private CuentaBitacoraNotaService $bitacoraNotaService,
    ) {}

    /**
     * @param  array<string, mixed>  $form
     * @return array{nro_cuenta: string}
     */
    public function guardarRegistro(int $pacienteId, int $pacientePlanId, ?string $nroCuentaExistente, array $form): array
    {
        $lineas = $form['lineas'] ?? [];
        if (! is_array($lineas)) {
            $lineas = [];
        }
        $paquete = $form['presupuestoPaquete'] ?? null;
        $tienePaquete = is_array($paquete) && ($paquete['id'] ?? null) !== null;
        if (count($lineas) < 1 && ! $tienePaquete) {
            throw ValidationException::withMessages([
                'form.lineas' => ['Incluya servicios en la grilla o seleccione un paquete.'],
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

        $tarifaId = isset($form['tarifaId']) && $form['tarifaId'] !== null ? (int) $form['tarifaId'] : null;
        if ($tarifaId === 0) {
            $tarifaId = null;
        }

        $fechaHospitalizacion = isset($form['fechaHospitalizacion']) ? trim((string) $form['fechaHospitalizacion']) : '';
        $fechaStr = $fechaHospitalizacion !== '' ? substr($fechaHospitalizacion, 0, 10) : null;

        $bloqueado = ($form['bloquearCuenta'] ?? '') === 'BLOQUEADO';
        $estadoCuenta = $bloqueado ? 'CANCELADO_LISTO_PARA_FACTURAR' : 'ACTIVO';

        return DB::transaction(function () use (
            $pacienteId,
            $pacientePlanId,
            $nroCuentaExistente,
            $form,
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
                    throw ValidationException::withMessages(['nro_cuenta' => ['Esta cuenta no admite actualización desde pre-facturación hospitalaria.']]);
                }
                if ((int) $cuenta->paciente_id !== $pacienteId) {
                    throw ValidationException::withMessages(['paciente_id' => ['El paciente no coincide con la cuenta.']]);
                }

                $registro = PreFacturacionHospitalariaRegistro::query()
                    ->where('id', (int) $cuenta->origen_id)
                    ->firstOrFail();

                $registro->payload = $form;
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
            $registro->payload = $form;
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
}
