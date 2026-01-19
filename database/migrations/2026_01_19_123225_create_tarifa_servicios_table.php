<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifa_servicios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tarifa_id')
                ->constrained('tarifas')
                ->restrictOnDelete();

            $table->foreignId('categoria_id')
                ->constrained('tarifa_categorias')
                ->restrictOnDelete();

            $table->foreignId('subcategoria_id')
                ->constrained('tarifa_subcategorias')
                ->restrictOnDelete();

            $table->char('servicio_codigo', 2);
            $table->char('codigo', 8);

            $table->string('nomenclador', 50)->nullable();
            $table->string('descripcion', 255);

            $table->decimal('precio_sin_igv', 14, 3)->default(0);
            $table->decimal('unidad', 14, 3)->default(1);

            $table->string('estado', 12)->default('ACTIVO');

            $table->timestamps();

            $table->unique(['tarifa_id', 'codigo']);
            $table->unique(['tarifa_id', 'categoria_id', 'subcategoria_id', 'servicio_codigo']);

            $table->index(['tarifa_id', 'categoria_id', 'subcategoria_id', 'estado']);
            $table->index(['tarifa_id', 'codigo']);
            $table->index(['tarifa_id', 'nomenclador']);
        });

        DB::statement("ALTER TABLE tarifa_servicios ADD CONSTRAINT chk_tarifa_servicios_servicio_codigo_format CHECK (servicio_codigo ~ '^[0-9]{2}$')");
        DB::statement("ALTER TABLE tarifa_servicios ADD CONSTRAINT chk_tarifa_servicios_codigo_format CHECK (codigo ~ '^[0-9]{2}\\.[0-9]{2}\\.[0-9]{2}$')");
        DB::statement("ALTER TABLE tarifa_servicios ADD CONSTRAINT chk_tarifa_servicios_estado CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");

        DB::statement("CREATE UNIQUE INDEX ux_tarifa_servicios_nomenclador_notnull ON tarifa_servicios (tarifa_id, nomenclador) WHERE nomenclador IS NOT NULL");
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa_servicios');
    }
};
