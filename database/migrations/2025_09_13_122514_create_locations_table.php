<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('facility_id')->nullable();
            $t->string('name');                                  // unique per company/facility
            $t->boolean('active')->default(true);
            $t->decimal('lat', 10, 7);
            $t->decimal('lng', 10, 7);
            $t->unsignedSmallInteger('radius_m')->default(150);  // >= 150, <= 1000 (validacija u kodu)
            $t->enum('outside_override', ['inherit','disallow'])->default('inherit');
            $t->boolean('require_gps_nfc')->default(false);
            $t->boolean('require_gps_ble')->default(false);
            $t->boolean('require_gps_qr')->default(false);
            $t->integer('min_rssi_override')->nullable();
            $t->unsignedInteger('required_visits_day')->default(0);
            $t->unsignedInteger('required_visits_week')->default(0);
            $t->unsignedInteger('required_visits_month')->default(0);
            $t->unsignedInteger('required_visits_year')->default(0);
            $t->softDeletes();
            $t->timestamps();

            $t->foreign('facility_id')->references('id')->on('facilities')->nullOnDelete();
            $t->unique(['company_id','facility_id','name']);
        });
    }
    public function down(): void { Schema::dropIfExists('locations'); }
};