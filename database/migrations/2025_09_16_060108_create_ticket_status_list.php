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
        if (!Schema::hasTable('tbl_ticketStatusList')) {
            Schema::create('tbl_ticketStatusList', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('description');
                $table->integer('sort');
                $table->timestamps();
            });
            DB::table('tbl_ticketStatusList')->insert([
                ['code' => 'P' , 'description' => 'Pending' , 'sort' => 1 ],
                ['code' => 'O' , 'description' => 'Open' , 'sort' => 2 ],
                ['code' => 'OG' , 'description' => 'On-Going' , 'sort' => 3 ],
                ['code' => 'RS' , 'description' => 'Resolved' , 'sort' => 4 ],
                ['code' => 'F' , 'description' => 'Failed' , 'sort' => 5 ],
                ['code' => 'QA' , 'description' => 'QA Testing' , 'sort' => 6 ],
                ['code' => 'CL' , 'description' => 'Closed Ticket' , 'sort' => 7 ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_ticketStatusList');
    }
};
