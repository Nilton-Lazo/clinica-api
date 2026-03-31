<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admision_presupuestos', function (Blueprint $table) {
            $table->string('codigo', 32)->nullable()->unique()->after('id');
        });

        foreach (DB::table('admision_presupuestos')->orderBy('id')->get(['id']) as $row) {
            $id = (int) $row->id;
            $s = (string) $id;
            $len = max(10, strlen($s));
            DB::table('admision_presupuestos')->where('id', $row->id)->update([
                'codigo' => str_pad($s, $len, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('admision_presupuestos', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
