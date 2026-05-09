<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_aperturas', function (Blueprint $table) {
            $table->decimal('ajuste_cierre', 15, 3)->nullable()->after('monto_cierre');
        });
    }

    public function down(): void
    {
        Schema::table('caja_aperturas', function (Blueprint $table) {
            $table->dropColumn('ajuste_cierre');
        });
    }
};
