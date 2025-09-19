<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = 'checkins';

        // Imena koja su nam se pojavila ranije za isti unique (company_id, client_event_id)
        $keepName = 'uniq_company_client_event'; // želimo da zadržimo baš ovo ime
        $duplicateNames = [
            'checkins_company_id_client_event_id_unique',
            'uniq_checkins_company_client_event',
        ];

        // 1) Proveri koji od ovih indeksa postoje i drop-uj duplikate
        $placeholders = implode(',', array_fill(0, count($duplicateNames), '?'));

        $existingDupes = DB::select(
            "
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND INDEX_NAME IN ($placeholders)
            GROUP BY INDEX_NAME
            ",
            array_merge([$table], $duplicateNames)
        );

        foreach ($existingDupes as $row) {
            $idx = $row->INDEX_NAME;
            // sigurniji raw SQL, jer Laravel dropUnique traži tačan naziv
            DB::statement("ALTER TABLE `$table` DROP INDEX `$idx`");
        }

        // 2) Ako naš ciljani indeks NE postoji, dodaj ga
        $existsKeep = DB::select(
            "
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND INDEX_NAME   = ?
            LIMIT 1
            ",
            [$table, $keepName]
        );

        if (empty($existsKeep)) {
            Schema::table($table, function (Blueprint $t) use ($keepName) {
                $t->unique(['company_id', 'client_event_id'], $keepName);
            });
        }
    }

    public function down(): void
    {
        $table = 'checkins';
        $keepName = 'uniq_company_client_event';

        // Vrati stanje tako što ćeš ukloniti naš "keep" indeks (ako postoji).
        $existsKeep = DB::select(
            "
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND INDEX_NAME   = ?
            LIMIT 1
            ",
            [$table, $keepName]
        );

        if (!empty($existsKeep)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$keepName`");
        }

        // (Opcionalno) ne vraćamo duplikate po imenu – nema potrebe.
    }
};
