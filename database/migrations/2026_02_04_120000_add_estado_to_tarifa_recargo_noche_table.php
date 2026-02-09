<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifa_recargo_noche', function (Blueprint $table) {
            $table->string('estado', 20)->default('ACTIVO')->after('hora_desde');
        });
    }

    public function down(): void
    {
        Schema::table('tarifa_recargo_noche', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
