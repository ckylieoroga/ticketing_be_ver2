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
        if (!Schema::hasTable('resolved_tickets')) {
            Schema::create('resolved_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_code');
                $table->string('client_username');
                $table->string('title');
                $table->string('assigned_to');
                $table->string('ticket_type');
                $table->string('ticket_status')->default('resolved');
                $table->string('solution');
                $table->string('comments')->nullable();
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
        Schema::dropIfExists('resolved_tickets');
    }
};
