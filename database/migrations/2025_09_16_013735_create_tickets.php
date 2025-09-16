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
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('title');
                $table->string('user_name'); // ticket creator
                $table->string('category'); // Incident Tickets, Service Request Tickets, Change Request Tickets, Other
                $table->string('description')->nullable(); //remarks
                $table->string('priority_status')->default('normal');
                $table->dateTime('deadline')->nullable();
                $table->string('ticket_status')->default('open');
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
        Schema::dropIfExists('tickets');
    }
};
