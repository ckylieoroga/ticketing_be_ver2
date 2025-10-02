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
        if(Schema::connection("sys_base")->hasTable('request_services')){
            DB::connection("sys_base")->table("request_services")->insert([
                // request , type , table , namespace , controller , function , condition
                ['request'=>'process_ticket','type'=>'assign','table'=>'tbl_assignment_personnel','namespace'=>'3','controller'=>'TicketServices','function' => 'main' ,'conditions' =>'conditions'],
                ['request'=>'process_ticket','type'=>'forward','table'=>'tbl_assignment_personnel','namespace'=>'3','controller'=>'TicketServices','function' => 'main' ,'conditions' =>'conditions'],
                ['request'=>'process_ticket','type'=>'reassign','table'=>'tbl_assignment_personnel','namespace'=>'3','controller'=>'TicketServices','function' => 'main' ,'conditions' =>'conditions'],
                ['request' => 'process_ticket','type' => 'resolved','table' => 'tbl_assignment_personnel','namepsace'=>'3' ,'controller'=>'TicketServices','function' => 'main' , 'conditions' => 'conditions'],
                ['request'=>'process_ticket','type'=>'return','table'=>'tbl_assignment_personnel','namespace'=>'3','controller'=>'TicketServices','function' => 'main' ,'conditions' =>'conditions'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 
    }
};
