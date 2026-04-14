<?php

namespace App\Modules\admision\services\citas;

use App\Core\support\CuentaOrigen;
use App\Modules\admision\models\AgendaCita;
use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\Cuenta;
use App\Modules\admision\models\Paciente;
use App\Modules\admision\models\RegistroEmergencia;

class CuentaSyncService
{
    public function syncFromRegistroEmergencia(RegistroEmergencia $r): void
    {
        $nc = trim((string) $r->numero_cuenta);
        if ($nc === '') {
            return;
        }

        $pacienteId = $this->resolvePacienteIdFromNumeroHc((string) $r->numero_hc);
        $hc = null;
        $nr = null;
        $nombre = trim((string) $r->apellidos_nombres);
        if ($pacienteId) {
            $p = Paciente::query()->find($pacienteId);
            if ($p) {
                $doc = trim((string) ($p->numero_documento ?? ''));
                $hc = $doc !== '' ? $doc : (string) ($p->nr ?? '');
                $nr = $p->nr !== null ? (string) $p->nr : null;
                $nombre = trim(($p->nombre_completo ?? $nombre));
            }
        }

        $fechaStr = $r->fecha ? $r->fecha->format('Y-m-d') : null;

        Cuenta::query()->updateOrCreate(
            [
                'origen' => CuentaOrigen::REGISTRO_EMERGENCIA->value,
                'origen_id' => $r->id,
            ],
            [
                'nro_cuenta' => $nc,
                'paciente_id' => $pacienteId,
                'paciente_plan_id' => $r->paciente_plan_id,
                'tarifa_id' => $r->tarifa_id,
                'fecha' => $fechaStr,
                'estado' => $r->estado !== null ? (string) $r->estado : null,
                'paciente_nombre' => $nombre !== '' ? $nombre : null,
                'hc' => $hc,
                'nr' => $nr,
            ]
        );
    }

    public function syncFromCitaAtencion(CitaAtencion $a, AgendaCita $cita): void
    {
        $nc = trim((string) $a->nro_cuenta);
        if ($nc === '') {
            return;
        }

        $fechaStr = $cita->fecha ? $cita->fecha->format('Y-m-d') : null;
        $estado = $cita->estado_atencion !== null && (string) $cita->estado_atencion !== ''
            ? (string) $cita->estado_atencion
            : (string) $cita->estado;

        Cuenta::query()->updateOrCreate(
            [
                'origen' => CuentaOrigen::CITA_ATENCION->value,
                'origen_id' => $a->id,
            ],
            [
                'nro_cuenta' => $nc,
                'paciente_id' => $cita->paciente_id,
                'paciente_plan_id' => $a->paciente_plan_id,
                'tarifa_id' => $a->tarifa_id,
                'fecha' => $fechaStr,
                'estado' => $estado !== '' ? $estado : null,
                'paciente_nombre' => $cita->paciente_nombre !== null ? (string) $cita->paciente_nombre : null,
                'hc' => $cita->hc !== null ? (string) $cita->hc : null,
                'nr' => $cita->nr !== null ? (string) $cita->nr : null,
            ]
        );
    }

    private function resolvePacienteIdFromNumeroHc(string $numeroHc): ?int
    {
        $numeroHc = trim($numeroHc);
        if ($numeroHc === '') {
            return null;
        }
        $p = Paciente::query()
            ->where(function ($q) use ($numeroHc) {
                $q->where('numero_documento', $numeroHc)->orWhere('nr', $numeroHc);
            })
            ->first();

        return $p ? (int) $p->id : null;
    }
}
