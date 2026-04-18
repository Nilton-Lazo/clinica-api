<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_medio_pago_forma_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medio_pago_id')->constrained('caja_medios_pago')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('forma_pago_id')->constrained('caja_formas_pago')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['medio_pago_id', 'forma_pago_id'], 'caja_medio_forma_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_medio_pago_forma_pago');
    }
};
