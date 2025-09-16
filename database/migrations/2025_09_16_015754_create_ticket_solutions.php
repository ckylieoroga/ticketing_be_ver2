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
        if (!Schema::hasTable('ticket_solutions')) {
            Schema::create('ticket_solutions', function (Blueprint $table) {
                $table->id();
                $table->string('solution_code')->unique();
                $table->string('ticket_code');
                $table->string('ticket_category');
                $table->string('title');
                $table->text('comments')->nullable();
                $table->string('assigned_to');
                $table->text('solution');
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
        Schema::dropIfExists('ticket_solutions');
    }
};
