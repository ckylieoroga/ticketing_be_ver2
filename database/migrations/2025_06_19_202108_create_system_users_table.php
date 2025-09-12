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
        if (!Schema::hasTable('system_users')){
            Schema::create('system_users', function (Blueprint $table) {
                $table->id();
                $table->string('user_name')->unique();
                $table->string('custom_user_name')->unique();
                $table->string('salutation')->nullable();
                $table->string('first_name');
                $table->string('middle_name')->nullable();
                $table->string('last_name');
                $table->string('suffix')->nullable();
                $table->string('nickname')->nullable();
                $table->string('email')->unique();
                $table->string('gender');
                $table->date('birthday');
                $table->integer('department')->nullable();
                $table->integer('designation')->nullable();
                $table->integer('employment_status')->nullable();
                $table->text('secret_key');
                $table->timestamp('last_login')->nullable();
                $table->integer('max_tokens');
                $table->integer('status');
                $table->dateTime('deleted_at')->nullable();
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_users');
    }
};
