<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Base\Auth\AuthService;
use Base\Tables\SystemUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
class ModuleList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // {--username=null : Username}
    // {--password=null : Password}
    protected $signature = 'app:module-list
                            {action : update or insert or delete or show}
                            {--values=[] : Array of values}
                            {--conditions=[] : Array of conditions }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $mainTable = "tblMenuList";
    protected $values = [];
    protected $conditions = [];
    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // if(!$this->hasAccess() || (!$this->option('username') && !$this->option('password'))) return $this->info('You are not authorized to access this file.');
        
            $command = $this->argument('action');
            if(!$command) return $this->info('Action required.');

            $columns =  DB::getSchemaBuilder()->getColumnListing($this->mainTable);
            if($command === "show") $this->info(json_encode($columns));
            else {
                switch ($command) {
                    case 'insert':
                        $this->values = json_decode($this->option('values') ?? []);
                        break;
                    case 'update':
                        $this->values = $this->option('values') ?? [];
                        break;
                }
                $operate = $this->$command();
                if(!$operate) $this->info('Error occured.');
                else $this->info('Success.');
            }
        } catch (\Exception $ex) {
            $this->info($ex->getMessage());
        }
    }
    private function hasAccess(){
        $userData = SystemUsers::select('secret_key')->where('custom_user_name',  $this->option('username'))->first();
        if($userData) return Crypt::decryptString($userData->secret_key) === $this->option('password');
        else return false;
    }
    private function insert(){
        try {
            if(is_object($this->values)) return DB::table($this->mainTable)->insertGetId((array)$this->values);
            foreach ($this->values as $key => $data) {
                if(is_object($data) || is_array($data)) DB::table($this->mainTable)->insertGetId((array)$data);
                else{
                    DB::table($this->mainTable)->insertGetId($secondaryData);
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }
    private function insertModules(){
        try {
            $toSave = [
                [
                    'roles' => 'CL,DV',
                    'path' => 'dashboard',
                    'name' => 'dashboard',
                    'title' => 'Dashboard',
                    'componentLink' => 'pages.Dashboard',
                    'iconType' => 'material',
                    'icon' => 'dashboard',
                    'isAuthenticated' => 1,
                    'isShow' => true
                ],
                [
                    'roles' => 'CL',
                    'path' => 'ticketList',
                    'name' => 'ticketList',
                    'title' => 'Tickets',
                    'componentLink' => 'pages.client.tickets',
                    'iconType' => 'fas',
                    'icon' => 'paperclip',
                    'isAuthenticated' => 1,
                    'isShow' => true
                ],
                [
                    'roles' => 'AD',
                    'path' => 'users',
                    'name' => 'user_list',
                    'title' => 'User List',
                    'componentLink' => '',
                    'iconType' => 'fas',
                    'icon' => 'user',
                    'isAuthenticated' => 1,
                    'isShow' => true
                ],
                [
                    'roles' => 'AD',
                    'path' => 'getTicket',
                    'name' => 'requestTicket',
                    'title' => '',
                    'componentLink' => '',
                    'iconType' => '',
                    'icon' => '',
                    'isAuthenticated' => 1,
                    'isShow' => false
                ],
                [
                    'roles' => 'AD',
                    'path' => 'ticketListing',
                    'name' => 'listingTicket',
                    'title' => '',
                    'componentLink' => '',
                    'iconType' => '',
                    'icon' => '',
                    'isAuthenticated' => 1,
                    'isShow' => false
                ]
            ];
    
            foreach ($toSave as $index => $data) {
                 DB::table($this->mainTable)->insertGetId($data);
            }
            return 'Success';
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

    }
}
