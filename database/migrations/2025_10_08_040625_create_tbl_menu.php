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
    

        Schema::create('tblMenuList', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('roles');
            $table->string('path');
            $table->string('name');
            $table->string('title');
            $table->string('componentLink');
            $table->string('iconType');
            $table->string('icon');
            $table->boolean('isAuthenticated')->default(true);
            $table->timestamps();
        });


        DB::unprepared('
            CREATE TRIGGER before_insert_tblMenuList
            BEFORE INSERT ON tblMenuList
            FOR EACH ROW
            BEGIN
                DECLARE next_code INT;
                SELECT IFNULL(MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)), 0) + 1 INTO next_code FROM tblMenuList;
                SET NEW.code = CONCAT("C", LPAD(next_code, 2, "0"));
            END
        ');
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tblMenuList');
    }
};
