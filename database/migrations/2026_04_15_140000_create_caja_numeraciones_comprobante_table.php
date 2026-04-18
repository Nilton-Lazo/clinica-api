<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_numeraciones_comprobante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_documento_id')->constrained('caja_tipos_documento')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('serie', 20);
            $table->unsignedInteger('numero')->default(1);
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamps();

            $table->unique(['tipo_documento_id', 'serie'], 'caja_numeracion_tipo_serie_unique');
            $table->index(['estado', 'tipo_documento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_numeraciones_comprobante');
    }
};
