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
        
        DB::connection("sys_base")->table('request_services')->insert([
            # request , type , table , namespace , controller , function  , condition
            ['request' => 'general_option','type'=>'list','table' => 'tbl_general_options','namespace' => '3','controller' => 'TicketSetup','function' => 'main' , 'conditions' => 'conditions'],
            ['request' => 'general_option','type'=>'get','table' => 'tbl_general_options','namespace' => '3','controller' => 'TicketSetup','function' => 'main' , 'conditions' => 'conditions'],
            
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
