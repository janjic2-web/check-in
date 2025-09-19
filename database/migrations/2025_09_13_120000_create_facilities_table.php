<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facilities', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('name', 120);
            // optional center of the facility (for map / defaults)
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->unsignedInteger('default_radius_m')->nullable(); // e.g., 150

            // outside rule at facility level (stricter than company)
            $t->enum('outside_override', ['inherit', 'disallow'])->default('inherit');

            $t->boolean('active')->default(true);

            // meta
                                        $t->enum('status', ['active', 'inactive'])->default('active');
                                        $t->string('address')->nullable();
                                        $t->string('zip')->nullable();

            $t->timestamps();

            $t->foreign('company_id')
              ->references('id')
              ->on('companies')
              ->cascadeOnDelete();

            // unique facility name within a company
            $t->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
