<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_emision_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('caja_apertura_id')->constrained('caja_aperturas')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nro_cuenta', 10);
            $table->string('cuenta_origen', 64);
            $table->string('numero_operacion', 120)->nullable();
            $table->json('snapshot');
            $table->timestamps();

            $table->index(['caja_apertura_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('nro_cuenta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_emision_comprobantes');
    }
};
