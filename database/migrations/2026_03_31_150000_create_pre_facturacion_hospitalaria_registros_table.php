<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_facturacion_hospitalaria_registros', function (Blueprint $table) {
            $table->id();
            $table->string('nro_cuenta', 10)->unique();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_facturacion_hospitalaria_registros');
    }
};
