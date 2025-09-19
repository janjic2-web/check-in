<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Osnovna identifikacija
            if (!Schema::hasColumn('companies', 'display_name')) {
                $table->string('display_name')->after('id');
            }
            if (!Schema::hasColumn('companies', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('companies', 'vat_pib')) {
                $table->string('vat_pib')->nullable()->after('legal_name');
            }

            // Adresa / jezik / zona
            if (!Schema::hasColumn('companies', 'address')) {
                $table->string('address')->nullable()->after('vat_pib');
            }
            if (!Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('companies', 'zip')) {
                $table->string('zip', 20)->nullable()->after('city');
            }
            if (!Schema::hasColumn('companies', 'country')) {
                $table->string('country', 2)->nullable()->after('zip'); // npr. "RS"
            }
            if (!Schema::hasColumn('companies', 'timezone')) {
                $table->string('timezone')->default('Europe/Belgrade')->after('country');
            }
            if (!Schema::hasColumn('companies', 'language')) {
                $table->string('language', 5)->default('sr')->after('timezone');
            }

            // Status / istek
            if (!Schema::hasColumn('companies', 'status')) {
                // enum: active|suspended
                $table->enum('status', ['active','suspended'])->default('active')->after('language');
            }
            if (!Schema::hasColumn('companies', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('status');
            }

            // Politike / podešavanja iz specifikacije (sa podrazumevanim vrednostima)
            if (!Schema::hasColumn('companies', 'allow_outside')) {
                $table->boolean('allow_outside')->default(true)->after('expires_at');
            }
            if (!Schema::hasColumn('companies', 'default_radius_m')) {
                $table->unsignedInteger('default_radius_m')->default(150)->after('allow_outside');
            }
            if (!Schema::hasColumn('companies', 'anti_spam_min_interval')) {
                $table->unsignedInteger('anti_spam_min_interval')->default(2)->after('default_radius_m'); // sekunde
            }
            if (!Schema::hasColumn('companies', 'offline_retention_hours')) {
                $table->unsignedInteger('offline_retention_hours')->default(24)->after('anti_spam_min_interval');
            }
            if (!Schema::hasColumn('companies', 'min_inout_gap_min')) {
                $table->unsignedInteger('min_inout_gap_min')->default(0)->after('offline_retention_hours');
            }
            if (!Schema::hasColumn('companies', 'ble_min_rssi')) {
                $table->integer('ble_min_rssi')->default(-80)->after('min_inout_gap_min');
            }

            // (opciono) plan_code ako želiš da ga imaš na companies
            // if (!Schema::hasColumn('companies', 'plan_code')) {
            //     $table->string('plan_code', 20)->nullable()->after('ble_min_rssi');
            // }

            // timestamps i soft deletes (ako fale)
            if (!Schema::hasColumn('companies', 'created_at')) {
                $table->timestamps();
            }
            if (!Schema::hasColumn('companies', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Bezbedno: skidamo samo kolone koje smo potencijalno dodali
            foreach ([
                'display_name','legal_name','vat_pib','address','city','zip','country',
                'timezone','language','status','expires_at','allow_outside','default_radius_m',
                'anti_spam_min_interval','offline_retention_hours','min_inout_gap_min','ble_min_rssi',
                // 'plan_code',
            ] as $col) {
                if (Schema::hasColumn('companies', $col)) {
                    $table->dropColumn($col);
                }
            }
            // Napomena: obično ne diramo timestamps/softDeletes u down(), jer ih možda koristiš drugde.
        });
    }
};
