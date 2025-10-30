<?php

namespace App\Http\Controllers;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Base\Models\DBQueries;
use Base\Tables\SystemUsers;


use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;


class ProgressController extends ParamSetup{
    protected Request $request;
    protected ?string $table;
    protected ?string $type;
    protected ?array $values;
    protected ?array $conditions;
    protected ?string $sort = null;
    protected ?int $limit = 0;
    protected ?string $key = null;
    private ?int $responseStatus = 200;
   

    protected ?string $code;
    protected ?string $status_code;
    protected ?array  $to_update;


    public function main(Request $request): JsonResponse
    {
        $this->request = $request;
        //TODO : modify request here
        if ($this->setTables()) {
            try {
                $this->querySetter();
                $this->generateParams();
                $this->execQuery();
            } catch (Exception $ex) {
                throw $ex;
            }
            return returnResponse($this->responseParser(), $this->responseStatus);
        } else return returnResponse("Table Not Found", 400);
    }
    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        $this->values = $request['values'] ?? null;
        $this->conditions = $request['conditions'] ?? null;
        $this->limit = $request['limit'] ?? null;
        $this->code = $this->values['ticket_code'] ?? $this->conditions['ticket_code'] ?? null;
        $ticket = collect($this->getLatestAssignee());
        $user = $this->getUser();
        if($this->type === "update"){
            if($user['user_type'] === "SP") $this->to_update['status'] = $this->status_code = $ticket->get('status');
            else $this->to_update['internal_status'] =  $this->status_code = $ticket->get('internal_status');
            $this->to_update[array_keys($this->to_update)[0]] = $this->status_code = $this->newTicketStatus(); 
        }
    }
    public function execQuery(): void{
        try {
            $ticket = collect($this->getLatestAssignee());
            DB::beginTransaction();
                $trail = new TrailController($this->code,$ticket->get('assigned_to') ?? null ,$this->status_code,false);
                $query = new DBQueries($this->table,$this->to_update,[
                    'ticket_code' => $this->code
                ]);
                $query->dbUpdate();
                $trail->executeTrail();
                $this->updateTicketRequestStatus();
            DB::commit();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
    private function updateTicketRequestStatus():void {
        $query = new DBQueries('ticket_request',
            ['status' => $this->status_code] , ['ticket_code' => $this->code]);
        $query->dbUpdate();
    }
    private function newTicketStatus(){
        $ticket =  collect($this->getTicketStatus(null));
        $newStatus = DB::table('tbl_status_list')
                ->where('sort',$ticket->get('sort') + 1)
                ->where('isInternal',$ticket->get('isInternal'))
                ->first();
        if(!$newStatus) throw new Exception("Ticket is already resolved", 402);
        return $newStatus->code;
    }
    public function getTicketStatus($status_code = null){
        $status_code = $status_code ?? $this->status_code;
        if(!$status_code) throw new Exception("Ticket status code is not define.", 401);
       
        return DB::table('tbl_general_options as go')->select('value','go.code','sl.sort','sl.isInternal')
                ->join('tbl_status_list as sl','sl.code','go.code')
                ->where('type','ticket_status')
                ->where('sl.code',$status_code)->first();
    }
    private function getTicketDetails(){
        if(!$this->code) throw new Exception("Ticket code required.", 400);
        $ticket = DB::table('ticket_request as tr')->leftJoin('ticket_details as dt','dt.ticket_code','tr.ticket_code')
                ->join('tbl_general_options as go','go.code','tr.status')
                ->select(
                    'dt.ticket_code as code',
                    'tr.ticket_number as number',
                    'tr.ticket_name as name',
                    'tr.user_name as assign_from',
                    'tr.description',
                    'tr.status',
                    'tr.priority_level',
                    'go.value'
                )
                ->where('tr.ticket_code',$this->code)
                ->first();
        if(!$ticket) throw new Exception("Ticket does not exist.", 401);
        return $ticket;
    }
    private function getLatestAssignee(){
        if(!$this->code) throw new Exception("Ticket code required.", 402);
        $user = $this->getUser();
        return DB::table("tbl_assignment_personnel as ap")
                        ->where('ap.assigned_to',$user->custom_user_name)
                        ->where('ap.ticket_code',$this->code)
                        ->first();
    }
    private function getUser(){
        if(!Auth::check()) throw new Exception("No user login found.", 401);
        $user = Auth::user();
        return SystemUsers::where('user_name', $user->name)->first();
    }
    private function responseParser(): mixed
    {
        return $this->filterResponse($this->response);
    }
    
    private function setTables(): bool
    {
        $request = $this->request;
        $requestQuery = $request['request'];
        $table = RequestServices::query()->where([
            "request" => $requestQuery,
            "type" => $request["type"]
        ])->first()?->table ?? null;
        if (!is_null($table)) {
            $this->table = $table;
            return true;
        }
        return false;
    }
    public function setParams(): array
    {
        return [
            'request' => $this->request,
            'table' => $this->table,
            'type' => $this->type,
            'values' => $this->values ?? null,
            'conditions' => $this->conditions ?? null,
            'sort' => $this->sort ?? "asc",
            'limit' => $this->limit ?? null,
            'key' => $this->key ?? "code",
        ];
    }
}