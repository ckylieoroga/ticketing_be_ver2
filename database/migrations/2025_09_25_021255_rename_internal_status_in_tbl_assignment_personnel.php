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
        Schema::table('tbl_assignment_personnel', function (Blueprint $table) {
            $table->renameColumn('internalStatus', 'internal_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_assignment_personnel', function (Blueprint $table) {
            $table->renameColumn('internal_status', 'internalStatus');
        });
    }
};
