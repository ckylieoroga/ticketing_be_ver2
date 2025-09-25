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
            DB::connection("sys_base")->table('request_fields')
            ->insert([
                // table ,query ,field ,type ,propery
                ['table'=>'tbl_assignment_personnel','query'=>'add','field'=>'ticket_code','type'=>'string','property'=>'required'],
                ['table'=>'tbl_assignment_personnel','query'=>'add','field'=>'assigned_from','type'=>'string','propery'=>'required'],
                ['table'=>'tbl_assignment_personnel','query'=>'add','field'=>'assigned_to','type'=>'string','propery'=>'required'],
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_request_field');
    }
};
