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
        DB::connection("sys_base")->table('request_fields')->insert([
            # table ,query , field , type , property
            ['table' => 'tbl_general_options','query'=>'get,list','field' =>'type', 'type' => 'string','property' => 'required']
         
            
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
