<?php

namespace App\Http\Controllers;

use Base\Models\DBQueries;
use Base\Tables\SystemUsers;
use Exception;
use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignmentController extends ParamSetup
{
    protected Request $request;
    protected ?string $table;
    protected ?string $type;
    protected ?array $values;
    protected $user;
    protected ?array $conditions;
    protected ?string $sort = null;
    protected ?int $limit = 0;
    protected ?string $key = null;
    private ?array $details = [];


    private ?string $code = null;
    private ?string $updateType;
    /**
     * @throws \Exception
     */
    public function main(Request $request): JsonResponse
    {
        $this->request = $request;
        //TODO : modify request here
        if ($this->setTables()) {
            try {

                $this->querySetter();
                $this->generateParams();
                $this->execQuery();

            } catch (\Exception $ex) {
                throw $ex;
            }
            return returnResponse($this->responseParser(), 200);
        } else return returnResponse("Table Not Found", 400);
    }

    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        $this->values = $request['values'];
        $this->conditions = $request['conditions'] ?? null;
        $this->code =  $this->values['ticket_code'] ?? $this->conditions['ticket_code'] ?? null;
        $this->updateType = $this->values['type'] ?? $this->conditions['type'] ?? null;
       if(in_array($this->type,['add','update','transfer'])) $this->validations();
    }
    public function execQuery():void{
        try {
            DB::beginTransaction();
                if($this->type === "add") $this->acceptTicket();
                else if(in_array($this->type,['update','transfer'])){
                    $trail = new TrailController($this->code,$this->values['assigned_to'] ?? null ,'');
                    // if($this->updateType === "RA"){
                        $trail->status = $this->getTicketStatus('Open')->code ?? '';
                        $trail->is_internal = true;
                        $trail->remarks =  $this->values['disclaimer'] ?? "Reassign";
                        $query = new DBQueries($this->table,[
                            'internal_assignee' => $this->values['assigned_to'],
                            'internal_disclaimer' => $this->values['disclaimer'] ?? null,
                            'internal_status' => $this->getTicketStatus('Open')->code ?? ''
                        ],
                        [
                            'ticket_code' => $this->code
                        ]);
                        $query->dbUpdate();
                        $trail->executeTrail();
                        $this->response = "Successfully reassign.";
                    // }
                }
            DB::commit();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }
    private function validations():void {
        $userReg = $this->getUser();
        $ticket = $this->getTicketDetails();
        if($this->type === 'add'){
            $assignment = DB::table('tbl_assignment_personnel')->where('ticket_code', $this->code)->exists();
            if($assignment) throw new Exception("This ticket has already been assigned.", 401);
        }else if($this->type === "update"){
            //  RA = Re-assign
            if($this->updateType === "RA"){
                if(!isset($this->values['assigned_to'])) throw new Exception("Assigned to is required.", 401);
            }
        }
    }
    private function acceptTicket(){
            $ticket = $this->getTicketDetails();
            $user = $this->getUser();
            $query = new DBQueries($this->table,[
                'ticket_code' => $this->code,
                'assigned_from' => $ticket->assign_from,
                'assigned_to' => $user->custom_user_name,
                'internal_assignee' => '',
                'status' => $this->getTicketStatus('Open',false)->code ?? '',
                'internal_status' =>  '' 
            ]);
            $query->dbInsert();
            $this->updateTicketStatus('Open');
            $this->response = "Successfully accepted ticket.";
    }
    private function updateTicketStatus($status): void{
         $query = new DBQueries('ticket_request',
            [
            'status' => $this->getTicketStatus($status,false)->code ?? '',
            ],
            [
                'ticket_code' => $this->code
            ]);
            $query->dbUpdate();
    }
    private function getTicketStatus($status,$isInternal = true ){
        return DB::table('tbl_general_options as go')->select('value','go.code')
                ->join('tbl_status_list as sl','sl.code','go.code')
                ->where('type','ticket_status')
                ->where('sl.isInternal',$isInternal)
                ->where('value',$status)->first();
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
        // private function isAuthorized(string $table, string $type)
    // {

    //     $userReg = $this->getUser();
    //     if (!$userReg) return false;


    //     if ($type === 'add') {
    //         $assignmentCode = $this->request['values']['ticket_code'] ?? null;
    //         if (!$assignmentCode) return false;

    //         //check if the ticket_code is already exiting in the table assignment or has been assigned
    //         $assignment = DB::table('tbl_assignment_personnel')
    //             ->select('ticket_code')
    //             ->where('ticket_code', $assignmentCode)
    //             ->first();

    //         if ($assignment) {
    //             throw new \Exception("This ticket has already been assigned.");
    //         } else {
    //             return true;
    //         }
    //     }

    //     //only currently logged in assigned_to person can update the assigned_to ticket
    //     if ($type === 'update' && $table === 'assignment') {
    //         $assignmentCode = $this->request['conditions']['ticket_code'] ?? null;
    //         if (!$assignmentCode) return false;

    //         $result = DB::table('ticket_request AS t')
    //             ->leftJoin('tbl_assignment_personnel AS a', 't.ticket_code', '=', 'a.ticket_code')
    //             ->where('t.ticket_code', $assignmentCode)
    //             ->where('a.assigned_to', $userReg->user_name)
    //             ->select('t.ticket_code')
    //             ->first();

    //         return (bool)$result;
    //     }
    // }
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
    private function getUser(){
        if(!Auth::check()) throw new Exception("No user login found.", 401);
        $user = Auth::user();
        return SystemUsers::where('user_name', $user->name)->first();
    }
    public function setParams(): array
    {
        return [
            'request' => $this->request,
            'table' => $this->table,
            'type' => $this->type,
            'values' => $this->values ?? null,
            'conditions' => $this->conditions ?? null,
            'sort' => $this->sort ?? null,
            'limit' => $this->limit ?? null,
            'key' => $this->key ?? null,
        ];
    }
}
