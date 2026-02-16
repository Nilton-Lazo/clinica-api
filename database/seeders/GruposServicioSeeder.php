<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GruposServicioSeeder extends Seeder
{
    public function run(): void
    {
        $grupos = [
            ['704101', 'Clinica', 'CLI', 1],
            ['704102', 'Laboratorio', 'LAB', 2],
            ['704103', 'Ecografía', 'IMAG', 3],
            ['704104', 'Procedimientos', 'PROC', 4],
            ['704105', 'Rayos X', 'IMAG', 5],
            ['704106', 'Tomografía', 'IMAG', 6],
            ['704107', 'Patología', 'PATO', 7],
            ['704108', 'Medicina Física', 'MEDF', 8],
            ['704109', 'Resonancia', 'IMAG', 9],
            ['704110', 'Honorarios Médicos', 'HMED', 10],
            ['704111', 'Medicinas', 'FARM', 11],
            ['704112', 'Equipos y Oxigeno', 'EQYO', 12],
        ];

        $now = now();
        foreach ($grupos as $i => $g) {
            DB::table('grupos_servicio')->insertOrIgnore([
                'codigo' => $g[0],
                'descripcion' => $g[1],
                'abrev' => $g[2],
                'orden' => $g[3],
                'estado' => 'ACTIVO',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
