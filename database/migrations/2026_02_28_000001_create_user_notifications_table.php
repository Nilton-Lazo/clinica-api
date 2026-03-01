<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('type', 20)->default('info');

            $table->string('entity_type', 100)->nullable();

            $table->string('action_type', 50)->nullable();

            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_name', 255)->nullable();

            $table->string('title', 255);
            $table->text('message');

            $table->jsonb('metadata')->nullable();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index('user_id',   'idx_user_notifications_user_id');
            $table->index('read_at',   'idx_user_notifications_read_at');
            $table->index('created_at','idx_user_notifications_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
