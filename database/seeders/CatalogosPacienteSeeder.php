<?php

namespace Database\Seeders;

use App\Core\support\RecordStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosPacienteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('paises')->updateOrInsert(
            ['iso2' => 'PE'],
            [
                'nombre' => 'Perú',
                'estado' => RecordStatus::ACTIVO->value,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('ubigeos')->updateOrInsert(
            ['codigo' => '150101'],
            [
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'LIMA',
                'estado' => RecordStatus::ACTIVO->value,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('ubigeos')->updateOrInsert(
            ['codigo' => '150103'],
            [
                'departamento' => 'LIMA',
                'provincia' => 'LIMA',
                'distrito' => 'ATE',
                'estado' => RecordStatus::ACTIVO->value,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('tipos_clientes')
            ->whereRaw('UPPER(TRIM(descripcion_tipo_cliente)) = ?', ['PARTICULAR/PARTICULAR'])
            ->update([
                'system_key' => 'DEFAULT_PARTICULAR',
                'estado' => 'ACTIVO',
                'updated_at' => now(),
            ]);

        DB::table('tipos_clientes')
            ->whereIn(DB::raw('UPPER(TRIM(descripcion_tipo_cliente))'), ['PRIVADA/PRIVADO', 'PRIVADO/PRIVADO'])
            ->update([
                'system_key' => 'DEFAULT_PRIVADO',
                'estado' => 'ACTIVO',
                'updated_at' => now(),
            ]);
    }
}
