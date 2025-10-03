<?php

namespace Ticket;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Base\Models\DBQueries;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Base\Tables\SystemUsers;
use App\Http\Classes\Ticket;

use GuzzleHttp\Client;


class TicketServices extends ParamSetup
{
    protected Request $request;
    protected ?string $table;
    protected ?string $type;
    protected ?array $values;
    protected ?array $conditions;
    protected ?string $sort = null;
    protected ?int $limit = 0;
    protected ?string $key = null;
    

    protected $assignedFrom;
    protected $assignedTo;
    protected $status;
    protected $internalStatus;
    protected $ticketCode;
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
                if($this->request['request'] === "process_ticket") $this->execAssignTicket();
                else $this->execQuery();
            } catch (Exception $ex) {
                return returnResponse(exceptionMessage($ex), 500);
            }
            return returnResponse($this->responseParser(), 200);
        } else return returnResponse("Table Not Found", 400);
    }
    private function execAssignTicket(): void{
        try {
            $default = [ 'table' => 'tbl_ticket_trail' , 'condition' =>[ [ 'ticket_code' ,$this->conditions['ticket_code'] ] ] ];
            $save = array( 'sortFunc' => (fn($table,$condition) => $this->getSortValue($table ? $table : $default['table'],$condition ? $condition : $default['condition']) ));
            $response = null;
            $user = $this->getUserDetails();
            $assignee = $user->custom_user_name;
            $ticketCode = $this->conditions['ticket_code'];
            $trailValues = fn($code,$assignee ,$status, $isInternal = true) =>  ['ticket_code' => $code , 'assignee' => $assignee , 'status' => $status , 'sort' => 0 ,'remarks' => '' , 'is_internal' => $isInternal];
            DB::beginTransaction();
                if($this->type === "assign"){
                    $ticketDetails = $this->getTicketRequest($ticketCode);
                    $status = 'O';
                    if($this->conditions['assigned_to']) $assignee = $this->conditions['assigned_to'];
                    $save = [
                        'tbl_assignment_personnel' => [
                            'values' => [
                                'ticket_code' => $ticketCode,
                                'assigned_from' => $ticketDetails->user_name,
                                'assigned_to' => $assignee,
                                'status' => $status,
                                'internalStatus' => $status
                            ],
                            'action' => 'add'
                        ],
                        'tbl_ticket_trail' => [
                            'values' => $trailValues($ticketCode,$assignee,$status),
                            'action' => 'add'
                        ]
                    ];
                    $response = "Successfully accept ticket.";
                }else if($this->type === "ongoing"){
                    $status = "OG";
                    $save = [
                        'tbl_assignment_personnel' => [
                            "action" => "update",
                            "conditions" => [
                                "ticket_code" => $ticketCode
                            ],
                            "values" => [
                                "status" =>$status,
                                "internalStatus" =>$status
                            ],
                        ],
                        'tbl_ticket_trail' => [
                            "action" => "add" ,
                            "values" => $trailValues($ticketCode,$assignee,$status)
                        ]
                    ];
                    $response = "Ongoing Ticket.";
                }else if($this->type === "resolved"){
                    $trailCount = DB::table('tbl_ticket_trail')->where('ticket_code',$ticketCode)->count();
                    $status = 'OG';
                    $internalStatus = "R";
                    // this is if client is satisfied with the result
                    $checkIfResolveInternally = DB::table("tbl_assignment_personnel")->where("ticket_code",$ticketCode)->where('internal_status',$internalStatus)->first();
                    if($checkIfResolveInternally) $status = $internalStatus; // change the client ticket status into 'R'
                    $save = [
                        'tbl_assignment_personnel' => [
                            'action' => 'update',
                            'conditions' => [
                                'ticket_code' => $ticketCode
                            ],
                            'values' => [
                                'status' => $status, // wait for client confirmation before resolving the ticket
                                'internal_status' => $internalStatus 
                            ]
                        ],
                        'tbl_ticket_trail' => [
                            'action' => 'add',
                            'values' => $trailValues($ticketCode,$assignee,$status),
                            'count' => 1, // count of another execution of the table
                            'count_start' => [ // changes in every execution ( index start at 0 )
                                [ 'is_internal' => false , 'status' => $status ],
                            ]
                        ],
                    ];
                    if($checkIfResolveInternally){
                        $save['tbl_ticket_trail'] = [
                            'action' => 'add',
                            'values' => $trailValues($ticketCode,$assignee,$status,false),
                        ];
                        // close and resolve the ticket after client response 
                        $this->insertCloseOrResolveTicket($ticketCode);
                        $this->insertCloseOrResolveTicket($ticketCode,true);
                    }
                    $response = "Successfully resolved ticket.";
                }else if($this->type === "return"){
                    $status = "O";
                    $internalStatus = "RT";
                    $lastAssignee = DB::table("tbl_ticket_trail")->where('ticket_code',$ticketCode)->orderBy("sort",'desc')->first();
                    $save = [
                        "tbl_assignment_personnel" => [
                            "action" => 'update' , 
                            'conditions' => [
                                'ticket_code' => $ticketCode
                            ],
                            'values' => [
                                'status' => $status,
                                'internal_status' => $internalStatus 
                            ],
                        ],
                        "tbl_ticket_trail" => [
                            "action" => "add",
                            "values" => $trailValues($ticketCode,$lastAssignee->assignee,$status),
                        ]
                    ];
                    $response = "Successfully return ticket.";
                }else if($this->type === "reassign"){
                    $status = "RA";
                    $assigned_to = $this->conditions['assigned_to'];
                    $save = [
                        "tbl_assignment_personnel" => [
                            "action" => "update",
                            "conditions" => [
                                "ticket_code" => $ticketCode
                            ],
                            "values" => [
                                "internal_assignee" => $assigned_to
                            ]
                        ],
                        "tbl_ticket_trail" => [
                            "action" => "add",
                            "values" =>  $trailValues($ticketCode,$assigned_to,$status),
                        ]
                    ];
                    $response = "Successfully reassign ticket.";
                }
                $this->runMultipleQuery($save);
            DB::commit();
            $this->response = $response;
        } catch (\Exception $ex) {
            throw new Exception( $ex->getMessage(), 500);
        }
    }
    private function insertCloseOrResolveTicket($code , $isCloseTicket = false){
        $table = $isCloseTicket ? 'closed_tickets' : 'resolved_tickets';

        $ticketDetails = $this->getTicketRequest($code);
        $assignedDetails = DB::table("tbl_assignment_personnel")->where('ticket_code',$code)->first();
        $validate = conditionalValidator([
            !$ticketDetails => "Can't process ticket because ticket does not exist.",
            !$assignedDetails => "Can't process ticket because ticket does not exist."
        ]);
        $toSave = [
            'ticket_code' => $code,
            'title' => $ticketDetails->ticket_name ?? '',
            'assigned_to' => $assignedDetails->assigned_to,
            'ticket_type' => $ticketDetails->type,
            'ticket_status' => 'R',
            'solution' => '',
            'comments' => ''
        ];
        if(!$isCloseTicket) $toSave['client_username'] = $ticketDetails->user_name;
        $query = DB::table($table)->insert($toSave);
    }
    private function runMultipleQuery($save){
        foreach ($save as $table => $data){
            if(isset($data['values']['sort'])) $data['values']['sort'] = $this->getSortValue('tbl_ticket_trail',[ ['ticket_code',($data['values']['ticket_code'] ?? null )] ]);
            $query = new DBQueries($table,$data['values'] ?? null,$data['conditions'] ?? null);
            $action = DBActions($data['action']) ?? $data['action'];
            $query->$action();
            if(isset($data['count']) && $data['count'] > 0){
                --$data['count'];
                $originalValue = $data['originalValue'] ?? $data['values'];
                $values = $originalValue;
                if(isset($data['count_start'][$data['count']])){
                    foreach ($data['count_start'][$data['count']] as $column => $value) {
                        if(!isset($values[$column])) throw new Exception("Column does not exist in the table.", 500);
                        $values[$column] = $value;
                    }
                }
                $toSave = [ 
                    $table  => [
                        'action' => $data['action'],
                        'values' => $values,
                        'count' => $data['count'] ?? 0,
                        'count_start' => $data['count_start'] ?? null,
                        'originalValue' => $originalValue
                    ]
                ];
                $this->runMultipleQuery($toSave);
            }
        }
    }
    private function getTicketRequest($ticket_code){
        if(!$ticket_code) throw new Exception("Ticket Code is required", 401);
        return DB::table("ticket_request")->where('ticket_code',$ticket_code)->first();
    }
    private function getUserDetails(){
        return DB::table('system_users')->where('user_name', Auth::user()->name)->first();
    }
    private function getSortValue($table,$condition = []){
        if(!$table) throw new Exception("Table name is required", 401);
        return DB::table($table)->where($condition)->count() + 1;
    }
    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        $this->values = $request['values'] ?? null;
        $this->conditions = $request['conditions'] ?? null;
        $this->limit = $request['limit'] ?? null;
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
    private function getUser(){
        return SystemUsers::where('user_name', Auth::user()->name)->first();
    }
}
