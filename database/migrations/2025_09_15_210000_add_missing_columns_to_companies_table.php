<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'display_name')) $table->string('display_name')->nullable();
            if (!Schema::hasColumn('companies', 'legal_name')) $table->string('legal_name')->nullable();
            if (!Schema::hasColumn('companies', 'address')) $table->string('address')->nullable();
            if (!Schema::hasColumn('companies', 'city')) $table->string('city')->nullable();
            if (!Schema::hasColumn('companies', 'zip')) $table->string('zip')->nullable();
            if (!Schema::hasColumn('companies', 'country')) $table->string('country')->nullable();
            if (!Schema::hasColumn('companies', 'timezone')) $table->string('timezone')->nullable();
            if (!Schema::hasColumn('companies', 'language')) $table->string('language')->nullable();
            if (!Schema::hasColumn('companies', 'status')) $table->string('status')->default('active');
            if (!Schema::hasColumn('companies', 'expires_at')) $table->dateTime('expires_at')->nullable();
            if (!Schema::hasColumn('companies', 'allow_outside')) $table->boolean('allow_outside')->default(false);
            if (!Schema::hasColumn('companies', 'default_radius_m')) $table->integer('default_radius_m')->nullable();
            if (!Schema::hasColumn('companies', 'anti_spam_min_interval')) $table->integer('anti_spam_min_interval')->nullable();
            if (!Schema::hasColumn('companies', 'offline_retention_hours')) $table->integer('offline_retention_hours')->nullable();
            if (!Schema::hasColumn('companies', 'min_inout_gap_min')) $table->integer('min_inout_gap_min')->nullable();
            if (!Schema::hasColumn('companies', 'ble_min_rssi')) $table->integer('ble_min_rssi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'display_name', 'legal_name', 'address', 'city', 'zip', 'country', 'timezone', 'language', 'status', 'expires_at',
                'allow_outside', 'default_radius_m', 'anti_spam_min_interval', 'offline_retention_hours', 'min_inout_gap_min', 'ble_min_rssi'
            ]);
        });
    }
};
