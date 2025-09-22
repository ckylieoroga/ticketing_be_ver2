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
        Schema::create('tbl_ticket_logs', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('ticket_code');
            $table->integer('status');
            $table->string('assignee');
            $table->integer('order');
            $table->enum('action', ['RA', 'UP','RS']);
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
