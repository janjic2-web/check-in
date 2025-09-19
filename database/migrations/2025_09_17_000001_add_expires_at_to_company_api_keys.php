<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('last_used_at');
        });
    }

    public function down(): void {
        Schema::table('company_api_keys', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
