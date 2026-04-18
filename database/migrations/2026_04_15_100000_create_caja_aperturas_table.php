<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_aperturas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique();
            $table->string('tipo', 16);
            $table->foreignId('user_entrega_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('user_recepciona_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('area_jefatura_id')->constrained('caja_areas_jefaturas')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('moneda', 8)->default('PEN');
            $table->decimal('monto_inicio', 15, 2);
            $table->string('usuario_caja', 64);
            $table->text('observaciones')->nullable();
            $table->timestampTz('apertura_at');
            $table->timestamps();

            $table->index(['tipo', 'apertura_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_aperturas');
    }
};
