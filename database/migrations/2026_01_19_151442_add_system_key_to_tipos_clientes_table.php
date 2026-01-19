<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_clientes', function (Blueprint $table) {
            $table->string('system_key', 60)->nullable()->unique()->after('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_clientes', function (Blueprint $table) {
            $table->dropUnique(['system_key']);
            $table->dropColumn('system_key');
        });
    }
};
