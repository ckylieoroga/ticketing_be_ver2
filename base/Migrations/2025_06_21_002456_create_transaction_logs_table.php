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
        if (!Schema::hasTable('transaction_logs')){
            Schema::create('transaction_logs', function (Blueprint $table) {
                $table->id();
                $table->dateTime('date_time');
                $table->string('user_name')->index();
                $table->string('module');
                $table->string('action');
                $table->string('code');
                $table->string('remarks')->nullable();
                $table->dateTime('deleted_at');
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
