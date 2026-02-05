<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de atención de la cita: PENDIENTE (recién generada) o ATENDIDO.
     * La columna "H. Ing." en el frontend muestra "P" cuando es PENDIENTE.
     */
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->string('estado_atencion', 12)->default('PENDIENTE')->after('estado');
        });

        DB::statement(
            "ALTER TABLE agenda_citas ADD CONSTRAINT agenda_citas_estado_atencion_check CHECK (estado_atencion IN ('PENDIENTE', 'ATENDIDO'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE agenda_citas DROP CONSTRAINT IF EXISTS agenda_citas_estado_atencion_check');
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropColumn('estado_atencion');
        });
    }
};
