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
        if (!Schema::connection('sys_base')->hasTable('error_codes')){
            Schema::connection('sys_base')->create('error_codes', function (Blueprint $table) {
                $table->id();
                $table->integer('code');
                $table->string('message')->nullable();
                $table->string('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_codes');
    }
};
