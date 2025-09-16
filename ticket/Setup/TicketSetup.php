<?php

namespace Ticket\Setup;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketSetup extends ParamSetup
{
    protected Request $request;
    protected ?string $table;
    protected ?string $type;
    protected ?array $values;
    protected ?array $conditions;
    protected ?string $sort = null;
    protected ?int $limit = 0;
    protected ?string $key = null;

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
                $this->execQuery();
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
        if ($request['type'] === 'add') {
            $this->values = $request['values'];
            $this->generateCode();
        } else if ($request['type'] === 'update') {
            $this->conditions = $request['conditions'];
            $this->values = $request['values'];
        } else if (in_array($request['type'], ['delete', 'list'])) {
            $this->conditions = $request['conditions'];
        }
    }
    public function execQuery(): void 
    {
        $values = (object) $this->request->values;
        dd($values);
        $code = $values->ticket_code;
        $reporter = $values->reporter;
        $assignee = $values->assignee;

        // return returnResponse("asdasd",200);
        
    }
    protected function insertTicketDetails($code,$details){

    }
    public function generateCode(): void
    {
        $crudDetails = DB::connection("sys_base")->table("crud_table_details")->where("module", $this->request["request"])->get();
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

        $this->values[$key] = $baseCodePrefix . str_pad((string) ++$count, $numDigits ?? 3, "0", STR_PAD_LEFT);
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
