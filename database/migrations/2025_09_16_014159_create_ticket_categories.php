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
        if (!Schema::hasTable('ticket_categories')) {
            Schema::create('ticket_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('ticket_type');
                $table->string('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            DB::table('ticket_categories')->insert([
                ['code' => 'I','ticket_type' => '','description' => 'Incident Tickets'],
                ['code' => 'S','ticket_type' => '','description' => 'Service Request Tickets'],
                ['code' => 'C','ticket_type' => '','description' => 'Change Request Tickets']
            ]);
        }
    }
/**  Incident Tickets
* quick restore tickets
* broken links, login issue, down site/system
*
* Service Request Tickets
* request for standard IT services or common maintenance
* update content, add user accs, request web analytics
*
* Change Request Tickets
* request modifications or upgrades in the system/website’ services or products
* add a new feature, module, plugin, etc.
*
* Other
* specify request
*/

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
