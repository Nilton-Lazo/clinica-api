<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->jsonb('old_values')->nullable()->after('entity_id');
            $table->jsonb('new_values')->nullable()->after('old_values');
            $table->string('entity_display_name', 255)->nullable()->after('new_values')->index();
            $table->text('description')->nullable()->after('action_label');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'old_values',
                'new_values',
                'entity_display_name',
                'description',
            ]);
        });
    }
};
