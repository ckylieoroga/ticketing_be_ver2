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
    private ?int $responseStatus = 200;
    private $ticketController;
   
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
                $this->execQuery();
            } catch (Exception $ex) {
                return returnResponse(exceptionMessage($ex), 500);
            }
            return returnResponse($this->responseParser(), $this->responseStatus);
        } else return returnResponse("Table Not Found", 400);
    }
    public function execQuery(): void{
        try {
            DB::beginTransaction();
                
            DB::commit();
        } catch (\Exception $ex) {
            $this->response = $ex->getMessage(); 
        }
    }
    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        $this->values = $request['values'] ?? null;
        $this->conditions = $request['conditions'] ?? null;
        $this->limit = $request['limit'] ?? null;
        if($this->type === 'add'){
            list($this->values['ticket_code'],$ticket_number) = $this->ticketController->generateTicketCode($this->request['request'],$this->table);

        }
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
