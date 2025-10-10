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
        Schema::table('tblMenuList', function (Blueprint $table) {
            $table->boolean('isShow')->default(true)->after("isAuthenticated");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tblMenuList', function (Blueprint $table) {
            $table->dropColumn(['isShow']);
        });
    }
};
