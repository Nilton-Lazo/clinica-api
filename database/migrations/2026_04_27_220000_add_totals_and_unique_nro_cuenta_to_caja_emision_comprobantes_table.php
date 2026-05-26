<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->decimal('total_paciente', 14, 2)->default(0)->after('fecha_vencimiento');
            $table->unsignedInteger('total_lineas')->default(0)->after('total_paciente');
            $table->unique('nro_cuenta', 'caja_emision_nro_cuenta_unique');
        });
    }

    public function down(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->dropUnique('caja_emision_nro_cuenta_unique');
            $table->dropColumn(['total_paciente', 'total_lineas']);
        });
    }
};
