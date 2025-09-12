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

        if (!Schema::connection('sys_base')->hasTable('namespace')) {
            Schema::connection('sys_base')->create('namespace', function (Blueprint $table) {
                $table->id();
                $table->string('namespace');
                $table->integer('status');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sys_base')->dropIfExists('namespace');
    }
};
