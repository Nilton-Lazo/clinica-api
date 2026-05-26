<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_numeracion_comprobante_correlativos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('numeracion_comprobante_id')
                ->constrained('caja_numeraciones_comprobante')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('next_numero');
            $table->timestamps();
            $table->unique('numeracion_comprobante_id', 'caja_numeracion_correlativo_numeracion_unique');
            $table->index('next_numero');
        });

        DB::statement("
            INSERT INTO caja_numeracion_comprobante_correlativos
                (numeracion_comprobante_id, next_numero, created_at, updated_at)
            SELECT
                n.id,
                GREATEST(
                    n.numero,
                    COALESCE(MAX(e.numero_emitido), 0) + 1,
                    1
                ),
                NOW(),
                NOW()
            FROM caja_numeraciones_comprobante n
            LEFT JOIN caja_emision_comprobantes e
                ON e.numeracion_comprobante_id = n.id
            GROUP BY n.id, n.numero
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_numeracion_comprobante_correlativos');
    }
};
