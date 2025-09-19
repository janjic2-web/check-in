<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'min_inout_gap_min')) {
                // Bez ->after() radi kompatibilnosti (kolona možda ne postoji svuda)
                $table->unsignedSmallInteger('min_inout_gap_min')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'min_inout_gap_min')) {
                $table->dropColumn('min_inout_gap_min');
            }
        });
    }
};