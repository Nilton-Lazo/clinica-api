<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('numero_operacion');
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->dropIndex(['fecha_vencimiento']);
            $table->dropColumn('fecha_vencimiento');
        });
    }
};
