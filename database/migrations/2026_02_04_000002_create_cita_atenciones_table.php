<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de atención de una cita: nro_cuenta (10 dígitos correlativo), hora_asistencia,
     * plan seleccionado, tarifa usada, y datos editables (parentesco_seguro, titular_nombre).
     */
    public function up(): void
    {
        Schema::create('cita_atenciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agenda_cita_id')->constrained('agenda_citas')->cascadeOnDelete();
            $table->string('nro_cuenta', 10)->nullable()->unique()->comment('Correlativo 10 dígitos, asignado al guardar');

            $table->time('hora_asistencia')->nullable()->comment('Hora en que acudió a su cita (checkbox)');

            $table->foreignId('paciente_plan_id')->nullable()->constrained('paciente_planes')->nullOnDelete();
            $table->foreignId('tarifa_id')->nullable()->constrained('tarifas')->nullOnDelete();

            $table->string('parentesco_seguro', 30)->nullable();
            $table->string('titular_nombre', 255)->nullable();

            $table->timestamps();

            $table->unique('agenda_cita_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cita_atenciones');
    }
};
