<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Dodaj kolone samo ako fale (MySQL nema IF NOT EXISTS za pojedinačna polja,
            // ali Laravel će baciti grešku ako pokušamo duplikat; ako si siguran da fale, ostavi ovako).
            if (!Schema::hasColumn('plans', 'max_users')) {
                $table->unsignedInteger('max_users')->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'features')) {
                $table->json('features')->nullable()->after('max_users');
            }
            if (!Schema::hasColumn('plans', 'price_month_cents')) {
                $table->unsignedInteger('price_month_cents')->nullable()->after('features');
            }
            if (!Schema::hasColumn('plans', 'price_year_cents')) {
                $table->unsignedInteger('price_year_cents')->nullable()->after('price_month_cents');
            }
            if (!Schema::hasColumn('plans', 'currency')) {
                $table->string('currency', 10)->default('EUR')->after('price_year_cents');
            }
            if (!Schema::hasColumn('plans', 'active')) {
                $table->boolean('active')->default(true)->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'active')) $table->dropColumn('active');
            if (Schema::hasColumn('plans', 'currency')) $table->dropColumn('currency');
            if (Schema::hasColumn('plans', 'price_year_cents')) $table->dropColumn('price_year_cents');
            if (Schema::hasColumn('plans', 'price_month_cents')) $table->dropColumn('price_month_cents');
            if (Schema::hasColumn('plans', 'features')) $table->dropColumn('features');
            if (Schema::hasColumn('plans', 'max_users')) $table->dropColumn('max_users');
        });
    }
};
