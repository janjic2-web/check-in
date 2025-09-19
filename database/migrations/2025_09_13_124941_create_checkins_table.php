<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('checkins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('location_id')->nullable()->constrained()->nullOnDelete();

            $t->enum('method', ['nfc','ble','qr','plain','manual']);
            $t->enum('action', ['in','out']);
            $t->enum('status', ['inside','outside']);

            $t->decimal('distance_m', 8, 2)->default(0);

            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();

            $t->json('details')->nullable();

            $t->string('device_id')->nullable();
            $t->enum('platform', ['ios','android'])->nullable();
            $t->string('app_version', 32)->nullable();

            $t->uuid('client_event_id')->nullable();

            $t->timestamps();

            // idempotentnost po kompaniji
            $t->unique(['company_id','client_event_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('checkins');
    }
};