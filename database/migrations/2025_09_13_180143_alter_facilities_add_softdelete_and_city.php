<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $t) {
            if (!Schema::hasColumn('facilities', 'city')) {
                $t->string('city')->nullable()->after('address'); // "mesto"
            }
            if (!Schema::hasColumn('facilities', 'deleted_at')) {
                $t->softDeletes(); // enables soft delete
            }
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $t) {
            if (Schema::hasColumn('facilities', 'city')) {
                $t->dropColumn('city');
            }
            if (Schema::hasColumn('facilities', 'deleted_at')) {
                $t->dropSoftDeletes();
            }
        });
    }
};
