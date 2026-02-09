<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tarifa_recargo_noche', function (Blueprint $table) {
            $table->time('hora_hasta')->default('07:00:00')->after('hora_desde');
        });
    }

    public function down(): void
    {
        Schema::table('tarifa_recargo_noche', function (Blueprint $table) {
            $table->dropColumn('hora_hasta');
        });
    }
};
