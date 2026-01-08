<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programaciones_medicas', function (Blueprint $table) {
            $table->id();

            $table->date('fecha');

            $table->foreignId('especialidad_id')->constrained('especialidades');
            $table->foreignId('medico_id')->constrained('medicos');
            $table->foreignId('consultorio_id')->constrained('consultorios');
            $table->foreignId('turno_id')->constrained('turnos');

            $table->integer('cupos')->default(0);

            $table->string('tipo', 20);

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('fecha');
            $table->index('estado');
            $table->index('tipo');

            $table->unique(['fecha', 'medico_id', 'turno_id'], 'pm_unique_medico_turno_fecha');
            $table->unique(['fecha', 'consultorio_id', 'turno_id'], 'pm_unique_consultorio_turno_fecha');
        });

        DB::statement("ALTER TABLE programaciones_medicas ADD CONSTRAINT pm_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("ALTER TABLE programaciones_medicas ADD CONSTRAINT pm_tipo_check CHECK (tipo IN ('NORMAL','EXTRAORDINARIA'))");
        DB::statement("ALTER TABLE programaciones_medicas ADD CONSTRAINT pm_cupos_check CHECK (cupos >= 1)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE programaciones_medicas DROP CONSTRAINT IF EXISTS pm_cupos_check');
        DB::statement('ALTER TABLE programaciones_medicas DROP CONSTRAINT IF EXISTS pm_tipo_check');
        DB::statement('ALTER TABLE programaciones_medicas DROP CONSTRAINT IF EXISTS pm_estado_check');
        Schema::dropIfExists('programaciones_medicas');
    }
};
