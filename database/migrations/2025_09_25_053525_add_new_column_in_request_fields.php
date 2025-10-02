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
        if(Schema::connection("sys_base")->hasTable('request_fields')){
            DB::connection("sys_base")->table("request_fields")->where('table','tbl_assignment_personnel')->where('field','ticket_code')
            ->update(['query' => 'add,assign,resolved,return,reassign']);
            DB::connection("sys_base")->table("request_fields")->where('table','tbl_assignment_personnel')->where('field','assigned_to')
            ->update(['query' => 'add,reassign']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_fields', function (Blueprint $table) {
            //
        });
    }
};
