<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('nombres')->after('username');
            $table->string('apellido_paterno')->after('nombres');
            $table->string('apellido_materno')->nullable()->after('apellido_paterno');
            $table->string('nivel')->after('email');
            $table->string('estado')->default('activo')->after('nivel');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'nivel',
                'estado',
            ]);
        });
    }
};
