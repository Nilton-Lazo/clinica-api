<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('nro_cuenta', 10)->unique();
            $table->string('origen', 32);
            $table->unsignedBigInteger('origen_id');
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->unsignedBigInteger('paciente_plan_id')->nullable()->index();
            $table->unsignedBigInteger('tarifa_id')->nullable()->index();
            $table->date('fecha')->nullable()->index();
            $table->string('estado', 50)->nullable();
            $table->string('paciente_nombre', 255)->nullable();
            $table->string('hc', 80)->nullable();
            $table->string('nr', 80)->nullable();
            $table->timestamps();

            $table->unique(['origen', 'origen_id']);
        });

        $this->backfillRegistroEmergencia();
        $this->backfillCitaAtenciones();
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }

    private function backfillRegistroEmergencia(): void
    {
        $rows = DB::table('registro_emergencia')
            ->whereNotNull('numero_cuenta')
            ->where('numero_cuenta', '!=', '')
            ->orderBy('id')
            ->get();

        foreach ($rows as $r) {
            $nro = trim((string) $r->numero_cuenta);
            if ($nro === '') {
                continue;
            }
            $pacienteId = $this->resolvePacienteIdFromNumeroHc((string) $r->numero_hc);
            $hc = null;
            $nr = null;
            $nombre = (string) $r->apellidos_nombres;
            if ($pacienteId) {
                $p = DB::table('pacientes')->where('id', $pacienteId)->first();
                if ($p) {
                    $doc = trim((string) ($p->numero_documento ?? ''));
                    $hc = $doc !== '' ? $doc : (string) ($p->nr ?? '');
                    $nr = $p->nr !== null ? (string) $p->nr : null;
                    $nombre = trim(
                        trim((string) ($p->apellido_paterno ?? '')) . ' ' .
                        trim((string) ($p->apellido_materno ?? '')) . ' ' .
                        trim((string) ($p->nombres ?? ''))
                    );
                    if ($nombre === '') {
                        $nombre = (string) $r->apellidos_nombres;
                    }
                }
            }

            $fecha = $r->fecha ?? null;
            $fechaStr = null;
            if ($fecha !== null && $fecha !== '') {
                $fechaStr = substr((string) $fecha, 0, 10);
            }

            DB::table('cuentas')->updateOrInsert(
                [
                    'origen' => 'REGISTRO_EMERGENCIA',
                    'origen_id' => (int) $r->id,
                ],
                [
                    'nro_cuenta' => $nro,
                    'paciente_id' => $pacienteId,
                    'paciente_plan_id' => $r->paciente_plan_id !== null ? (int) $r->paciente_plan_id : null,
                    'tarifa_id' => $r->tarifa_id !== null ? (int) $r->tarifa_id : null,
                    'fecha' => $fechaStr,
                    'estado' => $r->estado !== null ? (string) $r->estado : null,
                    'paciente_nombre' => $nombre,
                    'hc' => $hc,
                    'nr' => $nr,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function backfillCitaAtenciones(): void
    {
        $rows = DB::table('cita_atenciones as ca')
            ->join('agenda_citas as ac', 'ac.id', '=', 'ca.agenda_cita_id')
            ->whereNotNull('ca.nro_cuenta')
            ->where('ca.nro_cuenta', '!=', '')
            ->select([
                'ca.id as atencion_id',
                'ca.nro_cuenta',
                'ca.paciente_plan_id',
                'ca.tarifa_id',
                'ac.paciente_id',
                'ac.paciente_nombre',
                'ac.hc',
                'ac.nr',
                'ac.fecha',
                'ac.estado_atencion',
                'ac.estado',
            ])
            ->orderBy('ca.id')
            ->get();

        foreach ($rows as $r) {
            $nro = trim((string) $r->nro_cuenta);
            if ($nro === '') {
                continue;
            }
            $fechaStr = null;
            if ($r->fecha !== null && $r->fecha !== '') {
                $fechaStr = substr((string) $r->fecha, 0, 10);
            }
            $estado = $r->estado_atencion !== null && (string) $r->estado_atencion !== ''
                ? (string) $r->estado_atencion
                : (string) $r->estado;

            DB::table('cuentas')->updateOrInsert(
                [
                    'origen' => 'CITA_ATENCION',
                    'origen_id' => (int) $r->atencion_id,
                ],
                [
                    'nro_cuenta' => $nro,
                    'paciente_id' => $r->paciente_id !== null ? (int) $r->paciente_id : null,
                    'paciente_plan_id' => $r->paciente_plan_id !== null ? (int) $r->paciente_plan_id : null,
                    'tarifa_id' => $r->tarifa_id !== null ? (int) $r->tarifa_id : null,
                    'fecha' => $fechaStr,
                    'estado' => $estado !== '' ? $estado : null,
                    'paciente_nombre' => $r->paciente_nombre !== null ? (string) $r->paciente_nombre : null,
                    'hc' => $r->hc !== null ? (string) $r->hc : null,
                    'nr' => $r->nr !== null ? (string) $r->nr : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function resolvePacienteIdFromNumeroHc(string $numeroHc): ?int
    {
        $numeroHc = trim($numeroHc);
        if ($numeroHc === '') {
            return null;
        }
        $id = DB::table('pacientes')
            ->where(function ($q) use ($numeroHc) {
                $q->where('numero_documento', $numeroHc)->orWhere('nr', $numeroHc);
            })
            ->value('id');

        return $id !== null ? (int) $id : null;
    }
};
