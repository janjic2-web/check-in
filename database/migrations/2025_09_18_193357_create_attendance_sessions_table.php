<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('facility_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamp('in_at')->nullable();
            $table->timestamp('out_at')->nullable();
            $table->unsignedBigInteger('duration_sec')->nullable();
            $table->string('status', 32)->default('open');
            $table->boolean('under_threshold')->default(false);
            $table->json('in_meta')->nullable();
            $table->json('out_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // Foreign keys (ako su potrebni, možeš dodati restrikcije)
            // $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('set null');
            // $table->foreign('location_id')->references('id')->on('locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
