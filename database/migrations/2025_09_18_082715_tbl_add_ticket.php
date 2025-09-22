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
        Schema::create('ticket_request', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code');
            $table->text('ticket_number');
            $table->string('description');
            $table->string('type');
            $table->string('priority_level');
            $table->string('module');
            $table->string('status');
            $table->string('target_date');
            $table->string('resolved_date');
            $table->string('created_by');
            $table->string('deleted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
