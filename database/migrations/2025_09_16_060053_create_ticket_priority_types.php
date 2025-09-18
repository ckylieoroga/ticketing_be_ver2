<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('priority_types')) {
            Schema::create('priority_types', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('description');
                $table->timestamps();
                $table->softDeletes();
            });

            DB::table('priority_types')->insert([
                ['code'=>'MJ' , 'description' => 'Major'],
                ['code' => 'MN' , 'description' => 'Minor']
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priority_types');
    }
};
