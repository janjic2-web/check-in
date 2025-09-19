<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // --- Kolone ---
        if (!Schema::hasColumn('company_api_keys', 'is_superadmin')) {
            Schema::table('company_api_keys', function (Blueprint $t) {
                $t->boolean('is_superadmin')->default(false)->after('active');
            });
        }

        if (!Schema::hasColumn('company_api_keys', 'last_used_at')) {
            Schema::table('company_api_keys', function (Blueprint $t) {
                $t->timestamp('last_used_at')->nullable()->after('is_superadmin');
            });
        }

        if (!Schema::hasColumn('company_api_keys', 'deleted_at')) {
            Schema::table('company_api_keys', function (Blueprint $t) {
                $t->softDeletes()->after('updated_at');
            });
        }

        // --- Indeksi/unique ---
        if (!$this->hasIndex('company_api_keys', 'uniq_company_api_keys_key')) {
            Schema::table('company_api_keys', function (Blueprint $t) {
                $t->unique('key', 'uniq_company_api_keys_key');
            });
        }

        if (!$this->hasIndex('company_api_keys', 'idx_company_api_keys_company_active')) {
            Schema::table('company_api_keys', function (Blueprint $t) {
                $t->index(['company_id','active'], 'idx_company_api_keys_company_active');
            });
        }

        // --- FK na companies.id (ako fali) ---
        if (!$this->fkExists('company_api_keys', 'company_api_keys_company_id_foreign')) {
            Schema::table('company_api_keys', function (Blueprint $t) {
                $t->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        // obično patch ne “rollback”-ujemo da ne bismo gubili podatke;
        // ako hoćeš, možeš dodati dropUnique/dropIndex/dropSoftDeletes itd.
    }

    // --- Helpers ---

    private function hasIndex(string $table, string $index): bool
    {
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes($table);
            return array_key_exists(strtolower($index), array_change_key_case($indexes, CASE_LOWER));
        } catch (\Throwable $e) {
            $db = Schema::getConnection()->getDatabaseName();
            $rows = DB::select("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = ? AND table_name = ? AND index_name = ?
                LIMIT 1
            ", [$db, $table, $index]);
            return !empty($rows);
        }
    }

    private function fkExists(string $table, string $fkName): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $rows = DB::select("
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = ?
              AND TABLE_NAME = ?
              AND CONSTRAINT_NAME = ?
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            LIMIT 1
        ", [$db, $table, $fkName]);
        return !empty($rows);
    }
};
