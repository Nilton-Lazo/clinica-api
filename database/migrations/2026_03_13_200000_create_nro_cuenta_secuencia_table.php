<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nro_cuenta_secuencia', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('valor')->default(0);
        });
        DB::statement('INSERT INTO nro_cuenta_secuencia (id, valor) VALUES (1, 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('nro_cuenta_secuencia');
    }
};
