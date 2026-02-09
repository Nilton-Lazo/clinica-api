<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Precio y unidad con 4 decimales (estándar enterprise para servicios).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tarifa_servicios MODIFY precio_sin_igv DECIMAL(14,4) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE tarifa_servicios MODIFY unidad DECIMAL(14,4) NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE cita_atencion_servicios MODIFY precio_sin_igv DECIMAL(14,4) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE cita_atencion_servicios MODIFY precio_con_igv DECIMAL(14,4) NOT NULL DEFAULT 0');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE tarifa_servicios ALTER COLUMN precio_sin_igv TYPE DECIMAL(14,4), ALTER COLUMN precio_sin_igv SET DEFAULT 0');
            DB::statement('ALTER TABLE tarifa_servicios ALTER COLUMN unidad TYPE DECIMAL(14,4), ALTER COLUMN unidad SET DEFAULT 1');
            DB::statement('ALTER TABLE cita_atencion_servicios ALTER COLUMN precio_sin_igv TYPE DECIMAL(14,4), ALTER COLUMN precio_sin_igv SET DEFAULT 0');
            DB::statement('ALTER TABLE cita_atencion_servicios ALTER COLUMN precio_con_igv TYPE DECIMAL(14,4), ALTER COLUMN precio_con_igv SET DEFAULT 0');
        } else {
            $this->runGenericAlter();
        }
    }

    private function runGenericAlter(): void
    {
        // SQLite y otros: recrear columnas no es trivial; asumir MySQL/MariaDB o PostgreSQL en producción
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            // SQLite no soporta MODIFY; en desarrollo se puede ignorar o ejecutar migración completa
            return;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE tarifa_servicios MODIFY precio_sin_igv DECIMAL(14,3) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE tarifa_servicios MODIFY unidad DECIMAL(14,3) NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE cita_atencion_servicios MODIFY precio_sin_igv DECIMAL(14,3) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE cita_atencion_servicios MODIFY precio_con_igv DECIMAL(14,3) NOT NULL DEFAULT 0');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE tarifa_servicios ALTER COLUMN precio_sin_igv TYPE DECIMAL(14,3), ALTER COLUMN precio_sin_igv SET DEFAULT 0');
            DB::statement('ALTER TABLE tarifa_servicios ALTER COLUMN unidad TYPE DECIMAL(14,3), ALTER COLUMN unidad SET DEFAULT 1');
            DB::statement('ALTER TABLE cita_atencion_servicios ALTER COLUMN precio_sin_igv TYPE DECIMAL(14,3), ALTER COLUMN precio_sin_igv SET DEFAULT 0');
            DB::statement('ALTER TABLE cita_atencion_servicios ALTER COLUMN precio_con_igv TYPE DECIMAL(14,3), ALTER COLUMN precio_con_igv SET DEFAULT 0');
        }
    }
};
