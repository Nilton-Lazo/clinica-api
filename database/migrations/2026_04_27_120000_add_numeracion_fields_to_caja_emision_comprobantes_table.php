<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->foreignId('numeracion_comprobante_id')
                ->nullable()
                ->after('cuenta_origen')
                ->constrained('caja_numeraciones_comprobante')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('serie', 20)->nullable()->after('numeracion_comprobante_id');
            $table->unsignedInteger('numero_emitido')->nullable()->after('serie');
            $table->unique(
                ['numeracion_comprobante_id', 'numero_emitido'],
                'caja_emision_numeracion_numero_unique'
            );
            $table->index(['serie', 'numero_emitido']);
        });
    }

    public function down(): void
    {
        Schema::table('caja_emision_comprobantes', function (Blueprint $table) {
            $table->dropUnique('caja_emision_numeracion_numero_unique');
            $table->dropIndex(['serie', 'numero_emitido']);
            $table->dropConstrainedForeignId('numeracion_comprobante_id');
            $table->dropColumn(['serie', 'numero_emitido']);
        });
    }
};
