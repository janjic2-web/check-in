<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // CHECKINS
        Schema::table('checkins', function (Blueprint $table) {
            // Idempotency unique: (company_id, client_event_id)
            // Napomena: MySQL dozvoljava više NULL vrednosti u unique koloni.
            if (! $this->hasIndex('checkins', 'uniq_checkins_company_client_event')) {
                $table->unique(['company_id','client_event_id'], 'uniq_checkins_company_client_event');
            }

            if (! $this->hasIndex('checkins', 'idx_checkins_company_created')) {
                $table->index(['company_id','created_at'], 'idx_checkins_company_created');
            }

            if (! $this->hasIndex('checkins', 'idx_checkins_company_location_created')) {
                $table->index(['company_id','location_id','created_at'], 'idx_checkins_company_location_created');
            }
        });

        // LOCATIONS
        Schema::table('locations', function (Blueprint $table) {
            if (! $this->hasIndex('locations', 'idx_locations_company_active')) {
                $table->index(['company_id','active'], 'idx_locations_company_active');
            }
        });

        // COMPANY_API_KEYS
        Schema::table('company_api_keys', function (Blueprint $table) {
            if (! $this->hasIndex('company_api_keys', 'uniq_company_api_keys_key')) {
                $table->unique('key', 'uniq_company_api_keys_key');
            }
            if (! $this->hasIndex('company_api_keys', 'idx_company_api_keys_company_active')) {
                $table->index(['company_id','active'], 'idx_company_api_keys_company_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropUnique('uniq_checkins_company_client_event');
            $table->dropIndex('idx_checkins_company_created');
            $table->dropIndex('idx_checkins_company_location_created');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('idx_locations_company_active');
        });

        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->dropUnique('uniq_company_api_keys_key');
            $table->dropIndex('idx_company_api_keys_company_active');
        });
    }

    // Mali helper da ne pravimo duplikate indeksa kod ponovnog pokretanja migracija
    private function hasIndex(string $table, string $index): bool
    {
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes($table);
            return array_key_exists(strtolower($index), array_change_key_case($indexes, CASE_LOWER));
        } catch (\Throwable $e) {
            // Ako Doctrine nije dostupan, probajmo fallback (MySQL only)
            try {
                $conn = Schema::getConnection();
                $db = $conn->getDatabaseName();
                $rows = $conn->select("
                    SELECT 1
                    FROM information_schema.statistics
                    WHERE table_schema = ? AND table_name = ? AND index_name = ?
                    LIMIT 1
                ", [$db, $table, $index]);
                return !empty($rows);
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }
};
