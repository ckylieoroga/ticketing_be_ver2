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

        if (!Schema::connection('ticketing_sys')->hasTable('request_services')) {
            Schema::connection('ticketing_sys')->create('request_services', function (Blueprint $table) {
                $table->id();
                $table->string('request');
                $table->string('type');
                $table->string('table')->nullable();
                $table->string('namespace')->nullable();
                $table->string('controller')->nullable();
                $table->string('function')->nullable();
                $table->string('conditions')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sys_base')->dropIfExists('request_services');
    }
};
