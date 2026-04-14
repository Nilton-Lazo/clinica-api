<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuenta_bitacora_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('contenido');
            $table->timestamps();

            $table->index(['cuenta_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuenta_bitacora_notas');
    }
};
