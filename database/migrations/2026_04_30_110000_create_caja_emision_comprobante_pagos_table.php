<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_emision_comprobante_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emision_comprobante_id')->constrained('caja_emision_comprobantes')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('forma_pago_id')->constrained('caja_formas_pago')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('medio_pago_id')->constrained('caja_medios_pago')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('banco_tarjeta_id')->nullable()->constrained('caja_bancos_tarjetas')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('numero_operacion', 120)->nullable();
            $table->decimal('monto', 14, 2);
            $table->timestamps();

            $table->index(['emision_comprobante_id', 'created_at'], 'caja_emision_pago_emision_created_idx');
            $table->index(['medio_pago_id', 'created_at'], 'caja_emision_pago_medio_created_idx');
            $table->index(['forma_pago_id', 'created_at'], 'caja_emision_pago_forma_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_emision_comprobante_pagos');
    }
};
