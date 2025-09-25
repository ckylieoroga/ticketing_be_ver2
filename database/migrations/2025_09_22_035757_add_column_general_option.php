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
        DB::table('tbl_general_options')->insert([
            # type
            ['code' => 'TT01' ,'type' => 'ticket_type', 'value' => 'Incident' , 'status' => 1 ],
            ['code' => 'TT02' ,'type' => 'ticket_type', 'value' => 'Service Request' , 'status' => 1 ],
            ['code' => 'TT03' ,'type' => 'ticket_type', 'value' => 'Change Request' , 'status' => 1 ],
            # status for client
            ['code' => 'TS01' ,'type' => 'ticket_status', 'value' => 'Open' , 'status' => 1 ],
            ['code' => 'TS02' ,'type' => 'ticket_status', 'value' => 'Pending' , 'status' => 1 ],
            ['code' => 'TS03' ,'type' => 'ticket_status', 'value' => 'Closed' , 'status' => 1 ],
            # status for supports/devs
            ['code' => 'TS04' ,'type' => 'ticket_status', 'value' => 'Unassigned' , 'status' => 1 ],
            ['code' => 'TS05' ,'type' => 'ticket_status', 'value' => 'Assigned' , 'status' => 1 ],
            ['code' => 'TS06' ,'type' => 'ticket_status', 'value' => 'Transferred' , 'status' => 1 ],
            ['code' => 'TS07' ,'type' => 'ticket_status', 'value' => 'Resolved' , 'status' => 1 ],
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
