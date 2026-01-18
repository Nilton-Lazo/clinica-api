<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_clientes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 12)->unique();

            $table->unsignedBigInteger('tarifa_id');
            $table->unsignedBigInteger('iafa_id');
            $table->unsignedBigInteger('contratante_id');

            $table->string('descripcion_tipo_cliente', 255);

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->foreign('tarifa_id')->references('id')->on('tarifas')->restrictOnDelete();
            $table->foreign('iafa_id')->references('id')->on('iafas')->restrictOnDelete();
            $table->foreign('contratante_id')->references('id')->on('contratantes')->restrictOnDelete();

            $table->unique(['contratante_id', 'tarifa_id']);

            $table->index('estado');
            $table->index('tarifa_id');
            $table->index('iafa_id');
            $table->index('contratante_id');
        });

        DB::statement("ALTER TABLE tipos_clientes ADD CONSTRAINT tipos_clientes_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tipos_clientes DROP CONSTRAINT IF EXISTS tipos_clientes_estado_check');
        Schema::dropIfExists('tipos_clientes');
    }
};
