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
        Schema::table('ticket_details', function (Blueprint $table) {
            $table->boolean('status')->nullable()->after("category");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('tbl_user_details', function (Blueprint $table) {
            $table->dropColumn(['status']);
        });
    }
};
