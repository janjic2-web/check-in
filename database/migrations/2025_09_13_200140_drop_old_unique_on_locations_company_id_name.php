<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Ova migracija je sada prazna jer indeks više ne postoji
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // vrati stari UNIQUE ako ikad radiš rollback
            $table->unique(['company_id','name'], 'locations_company_id_name_unique');
        });
    }
};
