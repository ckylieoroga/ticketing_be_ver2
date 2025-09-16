<?php

namespace App\Http\Classes;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Ticket{

    private $ticket_code;
    public function __construct($code) {
        
    }

    public function issueNewTicket($details){
        DB::beginTransaction();


        DB::commit();
    }
    protected function checkIfTicketExist(){

    }
}

