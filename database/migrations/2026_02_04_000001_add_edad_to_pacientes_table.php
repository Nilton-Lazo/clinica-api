<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persistir edad en pacientes (calculada desde fecha_nacimiento al guardar).
     */
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->unsignedTinyInteger('edad')->nullable()->after('fecha_nacimiento');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn('edad');
        });
    }
};
