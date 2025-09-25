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
        if (!Schema::hasTable('tbl_ticket_logs')) {
            Schema::create('tbl_ticket_logs', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_code');
                $table->string('table');
                $table->string('previous_value');
                $table->string('new_value');
                $table->string('column');
                $table->string('action');
                $table->string('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_ticket_logs');
    }
};
