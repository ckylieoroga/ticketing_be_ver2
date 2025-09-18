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

use App\Http\Classes\Ticket;

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

    private $ticketController;
    private ?string $code = "";
   
    /**
     * @throws \Exception
     */
    public function __construct() {
        $this->ticketController = new Ticket;
    }
    public function main(Request $request): JsonResponse
    {
        $this->request = $request;
        //TODO : modify request here
        if ($this->setTables()) {
            try {
                $this->querySetter();
                $this->generateParams();
                if($this->type === 'add') $this->createNewTicket();
                else if(in_array($this->type,['list','get'])) $this->fetchTicketList();
            } catch (Exception $ex) {
                return returnResponse(exceptionMessage($ex), 500);
            }
            return returnResponse($this->responseParser(), 200);
        } else return returnResponse("Table Not Found", 400);
    }
   
    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        $this->values = $request['values'] ?? null;
        $this->conditions = $request['conditions'] ?? null;
        $this->limit = $request['limit'] ?? null;
        if($this->type === 'add') $this->generateTicketCode();
    }
    private function fetchTicketList(): void {
        try {
            $where = [];
            if($this->conditions['status']) $where[] = ['td.status',$this->conditions['status']];
            $this->response = DB::table('tbl_tickets')
            ->select(
                "tbl_tickets.ticket_code",
                "tbl_tickets.reporter_username",
                "tbl_tickets.assignee_username",
                "tbl_tickets.created_at as Posted",
                "td.*"
            )
            ->join('tbl_ticket_details as td' ,'td.ticket_code','tbl_tickets.ticket_code')
            ->where($where)
            ->get()
            ->map(function($data) {
                return[
                    'code' => $data->ticket_code,
                    'reporter' => $data->reporter_username,
                    'assignee' => $data->assignee_username,
                    'title' => $data->ticket_title,
                    'content' => $data->ticket_description,
                    'level' => $data->ticket_level,
                    'status' => $data->status,
                    'deadline' => $data->deadline,
                    'posted' => $data->Posted ? date('F d Y',strtotime($data->Posted)) : null
                ];
            });
            
        } catch (\Exception $ex) {
            $this->response = ['msg' => $ex->getMessage()];
        }
    }
    private function createNewTicket(): void{
        try {
            DB::beginTransaction();
                $reporter = $this->values['reporter'];
                $assignee = $this->values['assignee'];
                new DBQueries($this->table,['ticket_code'=>$this->code,'reporter_username'=>$reporter,'assignee_username' => $assignee])->dbInsert();
                new DBQueries('tbl_ticket_details',[
                    'ticket_code' => $this->code,
                    'ticket_title' => $this->values['title'] ?? '',
                    'ticket_description'=>$this->values['content'] ?? '',
                    'ticket_level'=>$this->values['priority_stat'],
                    'status' => $this->values['status'],
                    'deadline' => $this->values['deadline'],
                ])->dbInsert();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $this->response = $ex->getMessage();
        }
    }
    private function responseParser(): mixed
    {
        return $this->filterResponse($this->response);
    }
    public function generateTicketCode(): void{
        $crudDetails = DB::connection("sys_base")->table("crud_table_details")->where("module", $this->request['request'])->get();
        if ($crudDetails->isEmpty()) return;
        
        $key = $crudDetails->first(fn($cd) => $cd->type === "key")?->value ?? null;
     
        if (is_null($key)) throw new Exception("Setup Controller requires key type in crud_table_details");
        if (!Schema::hasColumn($this->table, $key)) throw new Exception("There is no $key in $this->table");

        $codePrefix = $crudDetails->first(fn($cd) => $cd->type === "code_prefix")?->value ?? null;
        if (is_null($codePrefix)) throw new Exception("Setup Controller requires code_prefix");

        $dateFormat = $crudDetails->first(fn($cd) => $cd->type === "date_format")?->value ?? null;
        $numDigits = $crudDetails->first(fn($cd) => $cd->type === "num_digits")?->value ?? null;

        $baseCodePrefix = $codePrefix . date($dateFormat ?? "Y");
        $count = DB::table($this->table)
            ->where($key, "like", $baseCodePrefix . "%")
            ->count();
        $this->code = $baseCodePrefix . str_pad((string) ++$count, $numDigits ?? 3, "0", STR_PAD_LEFT);
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
