<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paquete_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paquete_id')->constrained('paquetes')->cascadeOnDelete();
            $table->foreignId('tarifa_servicio_id')->constrained('tarifa_servicios')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['paquete_id', 'tarifa_servicio_id'], 'ux_paquete_servicio');
            $table->index('tarifa_servicio_id', 'ix_paquete_servicio_tarifa_servicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paquete_servicios');
    }
};
