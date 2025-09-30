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
        Schema::table('authorization_access', function (Blueprint $table) {
            $table->boolean('status')->nullable()->after("query");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('authorization_access', function (Blueprint $table) {
            $table->dropColumn(['status']);
        });
    }
};
