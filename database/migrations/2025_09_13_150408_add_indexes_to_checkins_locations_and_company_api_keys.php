<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // CHECKINS
        Schema::table('checkins', function (Blueprint $table) {
            // Idempotentnost po kompaniji (dozvoljava više NULL vrednosti u MySQL-u)
            $table->unique(['company_id', 'client_event_id'], 'uniq_checkins_company_client_event');

            // Najčešći query-i:
            $table->index(['company_id', 'location_id', 'created_at'], 'idx_checkins_company_location_created');
            $table->index(['company_id', 'user_id', 'created_at'], 'idx_checkins_company_user_created');
            $table->index(['company_id', 'device_id', 'created_at'], 'idx_checkins_company_device_created');
        });

        // LOCATIONS
        Schema::table('locations', function (Blueprint $table) {
            $table->index(['company_id', 'active'], 'idx_locations_company_active');
        });

        // COMPANY_API_KEYS
        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->unique('key', 'uniq_company_api_keys_key');
            $table->index('company_id', 'idx_company_api_keys_company');
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropUnique('uniq_checkins_company_client_event');
            $table->dropIndex('idx_checkins_company_location_created');
            $table->dropIndex('idx_checkins_company_user_created');
            $table->dropIndex('idx_checkins_company_device_created');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('idx_locations_company_active');
        });

        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->dropUnique('uniq_company_api_keys_key');
            $table->dropIndex('idx_company_api_keys_company');
        });
    }
};
