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
        if (!Schema::hasTable('assigned_tickets')) {
            Schema::create('assigned_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_code');
                $table->string('username');            // who's assigned
                $table->string('assignee_username');   // who assigned
                $table->string('ticket_status')->default('assigned');
                $table->string('internal_status')->default('ongoing');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_tickets');
    }
};
