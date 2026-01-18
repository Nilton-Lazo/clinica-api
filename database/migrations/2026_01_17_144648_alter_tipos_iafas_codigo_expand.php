<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tipos_iafas ALTER COLUMN codigo TYPE varchar(12)");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tipos_iafas ALTER COLUMN codigo TYPE varchar(3)");
    }
};
