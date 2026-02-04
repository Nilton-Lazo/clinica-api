<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quita el UNIQUE(programacion_medica_id, hora) para permitir agendar
     * una nueva cita en una hora que fue liberada (cita anulada = INACTIVO).
     * Solo puede haber una cita ACTIVA por slot; la lógica está en AgendaMedicaService.
     */
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropUnique('agenda_citas_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->unique(['programacion_medica_id', 'hora'], 'agenda_citas_unique_slot');
        });
    }
};
