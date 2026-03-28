<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 12)->unique();

            $table->string('tipo', 20);
            $table->string('nombre', 255);
            $table->string('dni_o_ruc', 11)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion', 255)->nullable();

            $table->string('estado', 12)->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
            $table->index('tipo');
            $table->index('nombre');
            $table->index('dni_o_ruc');
        });

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_estado_check CHECK (estado IN ('ACTIVO','INACTIVO','SUSPENDIDO'))");
        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_tipo_check CHECK (tipo IN ('ASISTENCIAL','ADMINISTRATIVO'))");
        DB::statement("ALTER TABLE clientes ADD CONSTRAINT clientes_dni_o_ruc_check CHECK (dni_o_ruc IS NULL OR dni_o_ruc ~ '^[0-9]{8}$' OR dni_o_ruc ~ '^[0-9]{11}$')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE clientes DROP CONSTRAINT IF EXISTS clientes_dni_o_ruc_check');
        DB::statement('ALTER TABLE clientes DROP CONSTRAINT IF EXISTS clientes_tipo_check');
        DB::statement('ALTER TABLE clientes DROP CONSTRAINT IF EXISTS clientes_estado_check');
        Schema::dropIfExists('clientes');
    }
};
