<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GeneralOptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:general-options
                            {type : option value}';
    //  run : php artisan app:general-options (args = option)
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $func = $this->argument('type');
        try {
            if($func === "ticket_status") $this->insertTicketStatusOption();
            else $this->info('Done.');
        } catch (\Exception $ex) {
            $this->info($ex->getMessage());
        }
    }
    private function insertTicketStatusOption(){
        DB::beginTransaction();
            $table = "tbl_general_options";
            for ($i=1; $i < 8 ; $i++) { 
                $code = 'TS0'.$i;
                DB::table($table)->where('code',$code)->delete();
            }
            DB::table('tbl_general_options')->insert([
                # status for client
                ['code' => 'TS01' ,'type' => 'ticket_status', 'value' => 'Pending' , 'status' => 1 ],
                ['code' => 'TS02' ,'type' => 'ticket_status', 'value' => 'Open' , 'status' => 1 ],
                ['code' => 'TS03' ,'type' => 'ticket_status', 'value' => 'Ongoing' , 'status' => 1 ],
                ['code' => 'TS04' ,'type' => 'ticket_status', 'value' => 'Closed' , 'status' => 1 ],
                # status for supports/devs
                ['code' => 'TS05' ,'type' => 'ticket_status', 'value' => 'Open' , 'status' => 1 ],
                ['code' => 'TS06' ,'type' => 'ticket_status', 'value' => 'Ongoing' , 'status' => 1 ],
                ['code' => 'TS07' ,'type' => 'ticket_status', 'value' => 'Resolved' , 'status' => 1 ],
                ['code' => 'TS08' ,'type' => 'ticket_status', 'value' => 'Transferred' , 'status' => 1 ],
                ['code' => 'TS09' ,'type' => 'ticket_status', 'value' => 'Closed' , 'status' => 1 ],
                ['code' => 'TS10' ,'type' => 'ticket_status', 'value' => 'Return' , 'status' => 1 ],
            ]);
            for ($i=1; $i < 5; $i++) { 
                $code = 'TS0'.$i;
                DB::table("tbl_status_list")->where('sort',$i)->where('isInternal',0)->update([ 'code' => $code ]);
            }
            $index = 1;
            for ($i=5; $i < 11 ; $i++) { 
                $code = 'TS0'.$i;
                DB::table("tbl_status_list")->where('sort',$index)->where('isInternal',1)->update([ 'code' => $code ]);
                $index++;
            }
        DB::commit();

        $this->info("Done.");
    }
}
