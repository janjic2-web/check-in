<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) Dodaj kolonu facility_id AKO je nema
        if (!Schema::hasColumn('locations', 'facility_id')) {
            Schema::table('locations', function (Blueprint $t) {
                $t->unsignedBigInteger('facility_id')->nullable()->after('company_id')->index();
            });
        }

        if (!Schema::hasColumn('checkins', 'facility_id')) {
            Schema::table('checkins', function (Blueprint $t) {
                $t->unsignedBigInteger('facility_id')->nullable()->after('company_id')->index();
            });
        }

        // Ako nema još tabele facilities (loš redosled), ne radimo ništa dalje – FK ćemo dodati kad tabela postoji.
        if (!Schema::hasTable('facilities')) {
            return;
        }

        // 2) Napravi "Default Facility" po kompaniji (samo ako kompanija nema nijedan facility)
    $companies = DB::table('companies')->select('id', 'display_name')->get();
        foreach ($companies as $c) {
            $hasFacility = DB::table('facilities')->where('company_id', $c->id)->exists();
            if (!$hasFacility) {
                $fid = DB::table('facilities')->insertGetId([
                    'company_id' => $c->id,
                    'name'       => 'Default Facility',
                    'active'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Veži sve postojeće lokacije te kompanije na novi Default Facility
                DB::table('locations')->where('company_id', $c->id)->update(['facility_id' => $fid]);
            }
        }

        // 2.b) Ako postoje checkinovi bez facility_id, popuni iz lokacije (denormalizacija radi performansi)
        DB::statement("
            UPDATE checkins ch
            JOIN locations l ON l.id = ch.location_id
            SET ch.facility_id = l.facility_id
            WHERE ch.facility_id IS NULL
        ");

        // 3) Dodaj FK tek nakon backfill-a (i samo ako nije već dodat)
        // Napomena: Laravel nema 'hasForeignKey', pa ćemo pokušati bezbedno – ako FK već postoji, MySQL će prijaviti duplikat.
        // Ako si već imao FK, slobodno preskoči ove dve sekcije.

        // locations -> facilities
        try {
            Schema::table('locations', function (Blueprint $t) {
                // nullOnDelete ovde nije logično (lokacije su "child"); koristimo cascade da brisanje facility-ja obori i lokacije (ili ostavi null po tvojoj politici).
                $t->foreign('facility_id')->references('id')->on('facilities')->cascadeOnDelete();
            });
        } catch (\Throwable $e) {
            // ignore if already exists
        }

        // checkins -> facilities (ovde je logičnije nullOnDelete)
        try {
            Schema::table('checkins', function (Blueprint $t) {
                $t->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // ignore if already exists
        }
    }

    public function down(): void
    {
        // Skidamo FK (ako postoje), pa kolone
        try {
            Schema::table('checkins', function (Blueprint $t) {
                $t->dropForeign(['facility_id']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('locations', function (Blueprint $t) {
                $t->dropForeign(['facility_id']);
            });
        } catch (\Throwable $e) {}

        if (Schema::hasColumn('checkins', 'facility_id')) {
            Schema::table('checkins', function (Blueprint $t) {
                $t->dropColumn('facility_id');
            });
        }

        if (Schema::hasColumn('locations', 'facility_id')) {
            Schema::table('locations', function (Blueprint $t) {
                $t->dropColumn('facility_id');
            });
        }
    }
};
