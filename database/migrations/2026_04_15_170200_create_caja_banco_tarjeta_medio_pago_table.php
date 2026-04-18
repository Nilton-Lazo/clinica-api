<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_banco_tarjeta_medio_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banco_tarjeta_id')->constrained('caja_bancos_tarjetas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('medio_pago_id')->constrained('caja_medios_pago')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['banco_tarjeta_id', 'medio_pago_id'], 'caja_banco_tarjeta_medio_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_banco_tarjeta_medio_pago');
    }
};
