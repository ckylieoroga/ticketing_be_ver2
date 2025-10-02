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
        Schema::dropIfExists('tbl_ticketStatusList');
        if(!Schema::hasTable('tbl_status_list')){
            Schema::create('tbl_status_list', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('description');
                $table->boolean('isInternal')->default(false);
                $table->integer('sort');
                $table->timestamp('created_at')->useCurrent();  //
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();  
            });
        }

        DB::table('tbl_status_list')->insert([
            ['code'=>'P','description' => 'Pending','isInternal'=>false,'sort' => 1],
            ['code'=>'O','description' => 'Open','isInternal'=>false,'sort' => 2],
            ['code'=>'OG','description' => 'Ongoing','isInternal'=>false,'sort' => 3],
            ['code'=>'C','description' => 'Closed','isInternal'=>false,'sort' => 4],

            ['code'=>'O','description' => 'Open','isInternal' => true,'sort' => 1],
            ['code'=>'OG','description' => 'Ongoing','isInternal' => true,'sort' => 2],
            ['code'=>'R','description' => 'Resolved','isInternal' => true,'sort' => 3],
            ['code'=>'C','description' => 'Closed','isInternal' => true,'sort' => 4],
            ['code'=>'RA','description' => 'Transferred','isInternal' => true,'sort' => 5],
            ['code' => 'RT' , 'description' => 'Return' ,'isInternal' => true , 'sort' => 6]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_status_list');
    }
};
