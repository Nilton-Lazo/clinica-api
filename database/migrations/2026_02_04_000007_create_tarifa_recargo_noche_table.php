<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reglas de recargo nocturno por tarifa y categoría (ej. Laboratorio +20% a partir de 19:00).
     */
    public function up(): void
    {
        Schema::create('tarifa_recargo_noche', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tarifa_id')
                ->constrained('tarifas')
                ->cascadeOnDelete();

            $table->foreignId('tarifa_categoria_id')
                ->constrained('tarifa_categorias')
                ->cascadeOnDelete();

            $table->decimal('porcentaje', 8, 2)->default(0);
            $table->time('hora_desde')->default('19:00:00');

            $table->timestamps();

            $table->unique(['tarifa_id', 'tarifa_categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa_recargo_noche');
    }
};
