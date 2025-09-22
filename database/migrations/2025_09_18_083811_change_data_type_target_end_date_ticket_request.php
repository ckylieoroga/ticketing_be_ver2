<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_request', function (Blueprint $table) {
            $table->date('target_date')->change();
            $table->date('resolved_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_request', function (Blueprint $table) {
            $table->string('target_date')->nullable()->change();
            $table->string('resolved_date')->nullable()->change();
        });
    }
};
