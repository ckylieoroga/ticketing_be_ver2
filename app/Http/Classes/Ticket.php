<?php

namespace App\Http\Classes;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Base\Tables\SystemUsers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Exception;
use Carbon\Carbon;
class Ticket{
    public function generateTicketTrail($request): void{
        $trailTable = DB::table('tbl_ticket_trail');
        if(!isset($request['ticketCode'])) throw new Exception("Request ticket code is required", 500);
        if(!isset($request['assignee'])) throw new Exception("Request assignee is required", 500);

        $trailCount = $trailTable->where('ticket_code',$request['ticketCode'])->count();
        $status = $this->nextTicketStatus(true,$request['ticketCode'],$request['isNext'] ?? false);
        $isExist = $this->getTicketTrails([ ['status', $status] , ['ticket_code',$request['ticketCode']] ],true);
        if(!$isExist){
            if($status === "R") $this->resolvedTickets($request['ticketCode']);
            $trailTable->insert([
                'ticket_code' => $request['ticketCode'],
                'assignee' => $request['assignee'],
                'status' =>   $status,
                'sort' => ++$trailCount,
                'isInternal' => $request['isInternal'] ?? false,
                'remarks' => ''
            ]);
        }
    }
    public function nextTicketStatus( $isInternal = false,  $ticketCode = '' , $isNext = false  ){
        $code = "O";
        if($isNext){
            $hasTrail = $this->getTicketTrails([ ['ticket_code',$ticketCode] ],true);
            if($hasTrail) $code = $hasTrail->status;
        }
        $ticketStatusList = DB::table('tbl_status_list')->where('isInternal',$isInternal)->get();
        $sort = fn($s) => ( $isNext ? $s + 1 : $s );
        foreach ($ticketStatusList as $status) {
            if($status->code === $code) return $this->getTicketStatus([ [ 'sort',$sort($status->sort) ]],$isInternal)->code ?? $code;
        }
    }
    public function resolvedTickets($ticketCode):void {
        if(!isset($ticketCode)) throw new Exception("Request ticket code is required", 500);

        $ticket = $this->getTicketRequest($ticketCode);
        $query = DB::table('resolved_tickets')->insert([
            "ticket_code" => $ticketCode,
            "client_username" => $ticket->user_name,
            "title" => '',
            "assigned_to" => '',
            "ticket_type" => $ticket->type,
            "ticket_status"  => 'R',
            "solution" => '',
            "comments" => ''
        ]);
    }
    private function getTicketTrails($condition,$latest = false){
        if($latest) return DB::table('tbl_ticket_trail')->where($condition)->orderBy('sort','desc')->first();
        return DB::table('tbl_ticket_trail')->where($condition)->get();
    }
    private function getTicketStatus($condition,$isInternal = false ){
        return DB::table('tbl_status_list')->where('isInternal',$isInternal)->where($condition)->first();
    }
    private function getTicketRequest($ticket_code){
        return DB::table('ticket_request')->where('ticket_code',$ticket_code)->first();
    }
    private function getUser(){
        return SystemUsers::where('user_name', Auth::user()->name)->first();
    }
}

