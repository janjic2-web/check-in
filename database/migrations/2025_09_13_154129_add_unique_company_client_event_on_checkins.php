<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) dodaj kolonu ako ne postoji
        if (!Schema::hasColumn('checkins', 'client_event_id')) {
            Schema::table('checkins', function (Blueprint $table) {
                $table->uuid('client_event_id')->nullable()->after('app_version');
            });
        }

        // 2) dodaj unique indeks ako ne postoji
        $dbName = DB::getDatabaseName();
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'checkins')
            ->where('INDEX_NAME', 'uniq_company_client_event')
            ->exists();

        if (!$exists) {
            Schema::table('checkins', function (Blueprint $table) {
                $table->unique(['company_id', 'client_event_id'], 'uniq_company_client_event');
            });
        }
    }

    public function down(): void
    {
        // Skini unique indeks ako postoji
        $dbName = DB::getDatabaseName();
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'checkins')
            ->where('INDEX_NAME', 'uniq_company_client_event')
            ->exists();

        if ($exists) {
            Schema::table('checkins', function (Blueprint $table) {
                $table->dropUnique('uniq_company_client_event');
            });
        }

        // (opciono) nemoj dirati kolonu u down da ne izgubiš podatke
        // Ako baš želiš: odkomentiraj sledeće dve linije:
        // if (Schema::hasColumn('checkins', 'client_event_id')) {
        //     Schema::table('checkins', fn (Blueprint $t) => $t->dropColumn('client_event_id'));
        // }
    }
};
