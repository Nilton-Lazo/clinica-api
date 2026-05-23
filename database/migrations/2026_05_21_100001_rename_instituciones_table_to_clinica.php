<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('instituciones') && ! Schema::hasTable('clinica')) {
            Schema::rename('instituciones', 'clinica');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('clinica') && ! Schema::hasTable('instituciones')) {
            Schema::rename('clinica', 'instituciones');
        }
    }
};
