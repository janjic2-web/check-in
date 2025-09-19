<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('locations', function (Blueprint $t) {
            // kompozitni indeks: company + facility + active
            $t->index(['company_id', 'facility_id', 'active'], 'loc_company_fac_active_idx');

            // jedinstveno ime unutar facility-ja (ako već ne postoji)
            $t->unique(['company_id', 'facility_id', 'name'], 'loc_company_fac_name_uniq');
        });
    }

    public function down(): void {
        Schema::table('locations', function (Blueprint $t) {
            $t->dropIndex('loc_company_fac_active_idx');
            $t->dropUnique('loc_company_fac_name_uniq');
        });
    }
};
