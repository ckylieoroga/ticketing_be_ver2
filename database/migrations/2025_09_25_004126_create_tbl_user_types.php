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
        if (!Schema::hasTable('tbl_user_types')) {
            Schema::create('tbl_user_types', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('description');
                $table->boolean('isActive')->default(true);
                $table->timestamps();
            });
        }
        DB::table('tbl_user_types')->insert([
            ['code'=>'CL','description' => 'Client'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_user_types');
    }
};
