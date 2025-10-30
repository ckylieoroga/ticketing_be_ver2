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
        Schema::create('users_access', function (Blueprint $table) {
            $table->id();
            $table->string("code");
            $table->string("description");
            $table->timestamps();
        });

        DB::unprepared('
            CREATE TRIGGER before_insert_users_access
            BEFORE INSERT ON users_access
            FOR EACH ROW
            BEGIN
                DECLARE next_code INT;
                SELECT IFNULL(MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)), 0) + 1 INTO next_code FROM users_access;
                SET NEW.code = CONCAT("AC", LPAD(next_code, 2, "0"));
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_access');
    }
};
