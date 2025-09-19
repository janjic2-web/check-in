<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::table('checkins', function (Blueprint $t) {
            if (!Schema::hasColumn('checkins', 'facility_id')) {
                $t->unsignedBigInteger('facility_id')->nullable()->after('location_id');
                $t->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
                // brzi filteri po kompaniji/objektu/vremenu
                $t->index(['company_id', 'facility_id', 'created_at'], 'chk_company_fac_created_idx');
            }
        });

        // Backfill facility_id iz locations (bez zaključavanja tabele)
        DB::statement("
            UPDATE checkins c
            JOIN locations l ON l.id = c.location_id
            SET c.facility_id = l.facility_id
            WHERE c.facility_id IS NULL
        ");
    }

    public function down(): void {
        Schema::table('checkins', function (Blueprint $t) {
            if (Schema::hasColumn('checkins', 'facility_id')) {
                $t->dropIndex('chk_company_fac_created_idx');
                $t->dropForeign(['facility_id']);
                $t->dropColumn('facility_id');
            }
        });
    }
};
