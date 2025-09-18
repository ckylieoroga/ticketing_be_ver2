<?php

namespace Options;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class OptionSetup extends ParamSetup
{
    protected Request $request;
    protected ?string $table;
    protected ?string $type;
    protected ?array $values;
    protected ?array $conditions;
    protected ?string $sort = null;
    protected ?int $limit = 0;
    protected ?string $key = null;
    protected ?string $option = null;


    /**
     * @throws \Exception
     */
    public function __call($name,$arguments){
        if($name === 'main') throw new Exception("Cannot use main function for Option setup.", 100);
        if(method_exists($this,$name)){
            try {
                $this->main($arguments[0]);
                return call_user_func_array([$this,$name],$arguments);
            } finally {
                return returnResponse($this->responseParser(), 200);
            }
        }
    }
    private function main(Request $request){
         $this->request = $request;
        if ($this->setTables()) {
            try {
                $this->querySetter();
                $this->generateParams();
            } catch (Exception $ex) {
                return returnResponse(exceptionMessage($ex), 500);
            }
        } else return returnResponse("Table Not Found", 400);
    }
    private function querySetter(): void
    {
        $request = $this->request;
        $this->type = $request['type'];
        $this->option = $request['options'] ?? null;
        if ($request['type'] === 'get') {
             $this->conditions = $request['conditions'];
        } else if ($request['type'] === 'list') {
            $this->values = $request['values'];
        }
    }
    private function getTicketStatusList(): void{
        $this->response = DB::table('tbl_ticketStatusList')->get()->map(function($data){
            return [
                'code' => $data->code,
                'label' => $data->description
            ];
        });
    }
    private function getTicketStatus(): void{
        $where = [];
        
        if(isset($this->conditions['code'])) $where[] = ['code',$this->conditions['code']];
        if(isset($this->conditions['sort'])) $where[] = ['sort',$this->conditions['sort']];
        $this->response = DB::table('tbl_ticketStatusList')
            ->where($where)
            ->get()->map(function($data){
                return [
                    'code' => $data->code,
                    'label' => $data->description
                ];
            });
    }
    private function getTicketCategories(): void{
        $this->response = DB::table('ticket_categories')
         ->get()->map(function($data){
                return [
                    'code' => $data->code,
                    'label' => $data->description
                ];
        });
    }
    private function getPriorityTypes(): void{
        $this->response = DB::table('priority_types')
         ->get()->map(function($data){
                return [
                    'code' => $data->code,
                    'label' => $data->description
                ];
        });
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
