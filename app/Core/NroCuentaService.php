<?php

namespace App\Core;

use App\Modules\admision\models\CitaAtencion;
use App\Modules\admision\models\RegistroEmergencia;
use Illuminate\Support\Facades\DB;

class NroCuentaService
{
    private const SECUENCIA_ID = 1;
    private const LENGTH = 10;

    public function next(): string
    {
        return DB::transaction(function () {
            $row = DB::table('nro_cuenta_secuencia')
                ->where('id', self::SECUENCIA_ID)
                ->lockForUpdate()
                ->first();

            $valor = $row ? (int) $row->valor : 0;

            if ($valor === 0) {
                $maxCita = $this->maxFromCitaAtenciones();
                $maxEmergencia = $this->maxFromRegistroEmergencia();
                $valor = max(0, $maxCita, $maxEmergencia);
            }

            $next = $valor + 1;

            DB::table('nro_cuenta_secuencia')
                ->where('id', self::SECUENCIA_ID)
                ->update(['valor' => $next]);

            return str_pad((string) $next, self::LENGTH, '0', STR_PAD_LEFT);
        });
    }

    private function maxFromCitaAtenciones(): int
    {
        $driver = DB::getDriverName();
        $query = CitaAtencion::query()
            ->whereNotNull('nro_cuenta')
            ->where('nro_cuenta', '!=', '');

        if ($driver === 'pgsql') {
            $query->orderByRaw('CAST(nro_cuenta AS INTEGER) DESC');
        } else {
            $query->orderByRaw('CAST(nro_cuenta AS UNSIGNED) DESC');
        }

        $last = $query->value('nro_cuenta');
        return $last !== null ? (int) $last : 0;
    }

    private function maxFromRegistroEmergencia(): int
    {
        $driver = DB::getDriverName();
        $query = RegistroEmergencia::query()
            ->whereNotNull('numero_cuenta')
            ->where('numero_cuenta', '!=', '');

        if ($driver === 'pgsql') {
            $query->orderByRaw('CAST(numero_cuenta AS INTEGER) DESC');
        } else {
            $query->orderByRaw('CAST(numero_cuenta AS UNSIGNED) DESC');
        }

        $last = $query->value('numero_cuenta');
        return $last !== null ? (int) $last : 0;
    }
}
