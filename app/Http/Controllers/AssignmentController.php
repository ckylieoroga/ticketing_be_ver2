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


    private ?string $code = "";

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
                if($this->type === 'add') $this->acceptTicket();
                else $this->execQuery();

            } catch (\Exception $ex) {
                return returnResponse(exceptionMessage($ex), 500);
            }
            return returnResponse($this->responseParser(), 200);
        } else return returnResponse("Table Not Found", 400);
    }

    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        if ($request['type'] === 'add') {
            $this->values = $request['values'];
            $this->code =  $this->request['values']['ticket_code'] ?? null;
            $this->isAuthorized($this->table, $this->type);
        } else if ($request['type'] === 'update') {
            $this->conditions = $request['conditions'];
            $this->values = $request['values'];
        } else if (in_array($request['type'], ['delete', 'list', 'get'])) {
            $this->conditions = $request['conditions'];
        }
    }
    private function acceptTicket(){
        DB::beginTransaction();
            $ticket = $this->getTicketDetails();
            $user = $this->getUser();
            $query = new DBQueries($this->table,[
                'ticket_code' => $this->code,
                'assigned_from' => $ticket->assign_from,
                'assigned_to' => $user->custom_user_name,
                'internal_assignee' => '',
                'status' => $this->getTicketStatus('Open',false)->code ?? '',
                'internal_status' => $this->getTicketStatus('Open')->code ?? '' 
            ]);
            $query->dbInsert();
            $this->updateTicketStatus('Open');
        DB::commit();
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
   
    private function isAuthorized(string $table, string $type)
    {

        $userReg = $this->getUser();
        if (!$userReg) return false;


        if ($type === 'add') {
            $assignmentCode = $this->request['values']['ticket_code'] ?? null;
            if (!$assignmentCode) return false;

            //check if the ticket_code is already exiting in the table assignment or has been assigned
            $assignment = DB::table('tbl_assignment_personnel')
                ->select('ticket_code')
                ->where('ticket_code', $assignmentCode)
                ->first();

            if ($assignment) {
                throw new \Exception("This ticket has already been assigned.");
            } else {
                return true;
            }
        }

        //only currently logged in assigned_to person can update the assigned_to ticket
        if ($type === 'update' && $table === 'assignment') {
            $assignmentCode = $this->request['conditions']['ticket_code'] ?? null;
            if (!$assignmentCode) return false;

            $result = DB::table('ticket_request AS t')
                ->leftJoin('tbl_assignment_personnel AS a', 't.ticket_code', '=', 'a.ticket_code')
                ->where('t.ticket_code', $assignmentCode)
                ->where('a.assigned_to', $userReg->user_name)
                ->select('t.ticket_code')
                ->first();

            return (bool)$result;
        }
    }
    private function responseParser(): mixed
    {
        return $this->filterResponse($this->response);
    }
    private function getTicketStatus($status,$isInternal = true , $increment = false){
        if($increment) $status = DB::table("tbl_status_list")->where('description',ucfirst($status))->where('isInternal',$isInternal)->select('sort','code','description')->first();
        
        return DB::table('tbl_general_options as go')->select('value','go.code')
                ->join('tbl_status_list as sl','sl.code','go.code')
                ->where('type','ticket_status')
                ->where('sl.isInternal',$isInternal)
                ->when($increment,function($query) use($status){
                    if(isset($status) && $status) $query->where('sl.sort',$status->sort + 1);
                })
                ->where('value',$status)->first();
    }
    private function getTicketDetails(){
        if(!$this->code) return;
        return DB::table('ticket_request as tr')->join('ticket_details as dt','dt.ticket_code','tr.ticket_code')
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
