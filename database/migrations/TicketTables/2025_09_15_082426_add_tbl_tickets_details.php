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
       Schema::create('tbl_ticket_details', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('ticket_code');
            $table->string('ticket_title');
            $table->string('ticket_description');
            $table->integer('ticket_level');
            $table->integer('status');
            $table->date('deadline');
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
