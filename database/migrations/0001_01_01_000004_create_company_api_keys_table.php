<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('company_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('key', 191)->unique();       // jedinstven API ključ
            $table->boolean('active')->default(true);
            $table->boolean('is_superadmin')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // omogućava revoke + istorija
            $table->index(['company_id','active']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('company_api_keys');
    }
};
