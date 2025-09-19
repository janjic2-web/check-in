<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // company_api_keys.company_id -> companies.id (CASCADE)
        Schema::table('company_api_keys', function (Blueprint $table) {
            if (! $this->fkExists('company_api_keys', 'company_api_keys_company_id_foreign')) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->cascadeOnDelete();
            }
        });

        Schema::table('checkins', function (Blueprint $table) {
            // checkins.company_id -> companies.id (RESTRICT)
            if (! $this->fkExists('checkins', 'checkins_company_id_foreign')) {
                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->restrictOnDelete();
                // Ako ipak želiš da brisanje firme briše istoriju:
                // ->cascadeOnDelete();
            }

            // checkins.location_id -> locations.id (SET NULL)
            if (! $this->fkExists('checkins', 'checkins_location_id_foreign')) {
                $table->foreign('location_id')
                    ->references('id')->on('locations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // skidamo FK-ove ako postoje (po imenu)
        $this->dropFkIfExists('checkins', 'checkins_company_id_foreign');
        $this->dropFkIfExists('checkins', 'checkins_location_id_foreign');
        $this->dropFkIfExists('company_api_keys', 'company_api_keys_company_id_foreign');
    }

    /** PROVERE BEZ DOCTRINE (MySQL) */
    private function fkExists(string $table, string $fkName): bool
    {
        $schema = DB::getDatabaseName();

        $sql = "SELECT COUNT(*) AS c
                  FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = ?
                   AND TABLE_NAME = ?
                   AND CONSTRAINT_NAME = ?
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'";

        $res = DB::select($sql, [$schema, $table, $fkName]);

        return !empty($res) && (int) $res[0]->c > 0;
    }

    private function dropFkIfExists(string $tableName, string $fkName): void
    {
        if ($this->fkExists($tableName, $fkName)) {
            Schema::table($tableName, function (Blueprint $t) use ($fkName) {
                $t->dropForeign($fkName);
            });
        }
    }
};
