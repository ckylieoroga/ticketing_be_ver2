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
        DB::connection("sys_base")->table('request_services')
        ->insert([
            // requestr , type , table , namesapce ,controller ,function ,condition 
            ['request'=>'assign','type' => 'add','table'=>'tbl_assignment_personnel','namespace' => '3','controller'=>'TicketServices','function' => 'main','conditions'=>'values'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_request_service');
    }
};
