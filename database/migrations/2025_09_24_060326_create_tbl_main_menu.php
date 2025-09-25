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
        if (!Schema::hasTable('tblMainMenu')) {
            Schema::create('tblMainMenu', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('description');
                $table->boolean('isActive')->default(true);
                $table->integer('sort');
                $table->string('useraccess');
                $table->enum('iconType',['material','fas']);
                $table->string('icon');
                $table->string('linkName');
                $table->timestamps();

                $table->index('code');
                $table->index('sort');
            });
        }
        DB::table('tblMainMenu')->insert([
            ['code' => 'M01','description'=>'Dashboard','isActive'=>1,'sort'=>1,'useraccess'=>'CL','iconType' =>'material','icon'=>'dashboard','linkName'=>'adminDashboard'],
            ['code' => 'M02','description'=>'Tickets','isActive'=>1,'sort'=>2,'useraccess'=>'CL','iconType'=>'fas','icon'=>'paperclip','linkName'=>'ticketList'],
            ['code' => 'M03','description'=>'Team','isActive'=>1,'sort'=>3,'useraccess'=>'CL','iconType'=>'fas','icon'=> 'user-friends','linkName'=>'team_list'],
            ['code' => 'M04','description'=>'Users','isActive'=>1,'sort'=>4,'useraccess'=>'CL','iconType'=>'fas','icon'=> 'user','linkName'=>'user_list'],
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblMainMenu');
    }
};
