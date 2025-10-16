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
        if (Schema::hasTable('tbl_assignment_personnel')) {
            Schema::table('tbl_assignment_personnel', function (Blueprint $table) {
              $table->string('internal_disclaimer')->nullable()->after("internal_status");
            });
            
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_assignment_personnel', function (Blueprint $table) {
            $table->dropColumn(['internal_disclaimer']);
        });
    }
};
