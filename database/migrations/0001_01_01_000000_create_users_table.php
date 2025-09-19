<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('username', 64)->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('name', 64);
            $table->string('surname', 64)->nullable();
            $table->enum('role', ['admin','employee','facility_admin','superadmin','company_admin'])->default('employee');
            $table->string('phone', 32)->nullable();
            $table->string('employee_id', 32)->nullable();
            $table->enum('status', ['active','suspended'])->default('active');
            $table->unsignedInteger('required_checkins_day')->default(0);
            $table->unsignedInteger('required_checkins_week')->default(0);
            $table->unsignedInteger('required_checkins_month')->default(0);
            $table->unsignedInteger('required_checkins_year')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }
};
