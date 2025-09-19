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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique(); // free, starter, pro, enterprise
            $table->string('name', 64);
            $table->unsignedInteger('user_limit')->nullable();
            $table->boolean('facility_admin_enabled')->default(false);
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 8)->default('RSD');
            $table->enum('period', ['monthly', 'yearly'])->default('monthly');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
