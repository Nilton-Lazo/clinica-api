<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 3)->unique();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('duracion_minutos')->default(0);

            $table->string('descripcion', 255);

            $table->string('tipo_turno', 12);
            $table->string('jornada', 10);

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('tipo_turno');
            $table->index('jornada');
        });

        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_tipo_check CHECK (tipo_turno IN ('NORMAL','ADICIONAL','EXCLUSIVO'))");
        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_jornada_check CHECK (jornada IN ('MANANA','TARDE','NOCHE'))");
        DB::statement("ALTER TABLE turnos ADD CONSTRAINT turnos_duracion_check CHECK (duracion_minutos >= 0)");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE turnos DROP CONSTRAINT IF EXISTS turnos_duracion_check');
        DB::statement('ALTER TABLE turnos DROP CONSTRAINT IF EXISTS turnos_jornada_check');
        DB::statement('ALTER TABLE turnos DROP CONSTRAINT IF EXISTS turnos_tipo_check');
        DB::statement('ALTER TABLE turnos DROP CONSTRAINT IF EXISTS turnos_estado_check');
        Schema::dropIfExists('turnos');
    }
};
