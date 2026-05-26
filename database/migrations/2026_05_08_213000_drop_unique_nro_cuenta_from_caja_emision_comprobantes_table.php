<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->dropUnique('caja_emision_nro_cuenta_unique');
            $table->index('nro_cuenta', 'caja_emision_nro_cuenta_idx');
        });
    }

    public function down(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->dropIndex('caja_emision_nro_cuenta_idx');
            $table->unique('nro_cuenta', 'caja_emision_nro_cuenta_unique');
        });
    }
};
