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
        Schema::create('tbl_ticket_trail', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code');
            $table->string('assignee');
            $table->string('status');
            $table->integer('sort');
            $table->string('remarks')->nullable();
            $table->boolean('isInternal')->default(true);
            $table->timestamp('created_at')->useCurrent(); 
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_ticket_trail');
    }
};
