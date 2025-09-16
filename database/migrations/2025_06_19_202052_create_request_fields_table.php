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
        if (!Schema::connection('ticketing_sys')->hasTable('request_fields')) {
            Schema::connection('ticketing_sys')->create('request_fields', function (Blueprint $table) {
                $table->id();
                $table->string('table');
                $table->string('query');
                $table->string('field');
                $table->string('type');
                $table->string('property')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sys_base')->dropIfExists('request_fields');
    }
};
