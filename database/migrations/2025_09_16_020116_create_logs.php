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
        if (!Schema::connection('sys_base')->hasTable('logs')) {
            Schema::connection('sys_base')->create('logs', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('username');
                $table->string('assignee')->nullable();
                $table->text('description')->nullable();
                $table->dateTime('deadline')->nullable();
                $table->string('ticket_status')->nullable();
                $table->string('internal_status')->nullable();
                $table->dateTime('closed_at')->nullable();
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
        Schema::dropIfExists('logs');
    }
};
