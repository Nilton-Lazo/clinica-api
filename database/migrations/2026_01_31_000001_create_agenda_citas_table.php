<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_citas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 10);
            $table->foreignId('programacion_medica_id')->constrained('programaciones_medicas');

            $table->date('fecha');
            $table->time('hora');
            $table->integer('orden')->default(0);

            $table->foreignId('paciente_id')->constrained('pacientes');
            $table->string('hc', 40)->nullable();
            $table->string('nr', 40)->nullable();
            $table->string('paciente_nombre', 255);
            $table->string('sexo', 20)->nullable();
            $table->integer('edad')->nullable();
            $table->string('titular_nombre', 255)->nullable();

            $table->string('cuenta', 120)->nullable();
            $table->foreignId('iafa_id')->nullable()->constrained('iafas');

            $table->string('motivo', 120)->nullable();
            $table->text('observacion')->nullable();
            $table->string('autorizacion_siteds', 60)->nullable();

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->unique(['programacion_medica_id', 'hora'], 'agenda_citas_unique_slot');
            $table->index(['fecha', 'estado']);
            $table->index(['programacion_medica_id', 'orden']);
        });

        DB::statement("ALTER TABLE agenda_citas ADD CONSTRAINT agenda_citas_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement('ALTER TABLE agenda_citas ADD CONSTRAINT agenda_citas_orden_check CHECK (orden >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE agenda_citas DROP CONSTRAINT IF EXISTS agenda_citas_orden_check');
        DB::statement('ALTER TABLE agenda_citas DROP CONSTRAINT IF EXISTS agenda_citas_estado_check');
        Schema::dropIfExists('agenda_citas');
    }
};
