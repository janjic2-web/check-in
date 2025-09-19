<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('display_name');
            $table->string('legal_name')->nullable();
            $table->string('vat_pib')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('language', 8)->default('en');
            $table->enum('status', ['active','suspended'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('default_radius_m')->default(150);
            $table->unsignedInteger('anti_spam_min_interval')->default(2);
            $table->unsignedInteger('offline_retention_hours')->default(24);
            $table->unsignedInteger('min_inout_gap_min')->default(0);
            $table->integer('ble_min_rssi')->default(-80);
            $table->boolean('require_gps_checkin')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
