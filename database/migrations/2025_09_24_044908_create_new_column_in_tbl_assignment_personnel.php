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
            if (!Schema::hasColumns('tbl_assignment_personnel', ['status','internalStatus'])) {
                $table->string('status')->nullable()->after('assigned_to');
                $table->string('internalStatus')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_column_in_tbl_assignment_personnel');
    }
};
