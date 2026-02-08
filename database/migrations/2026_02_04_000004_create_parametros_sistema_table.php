<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros configurables del sistema (IGV, etc.).
     */
    public function up(): void
    {
        Schema::create('parametros_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 64)->unique();
            $table->string('valor', 255);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        DB::table('parametros_sistema')->insert([
            'clave' => 'igv_porcentaje',
            'valor' => '18',
            'descripcion' => 'Porcentaje de IGV aplicado a servicios (ej. 18)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_sistema');
    }
};
