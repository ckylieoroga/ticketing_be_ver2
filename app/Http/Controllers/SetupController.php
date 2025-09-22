<?php

namespace App\Http\Controllers;

use Exception;
use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupController extends ParamSetup
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
                if ($this->table === 'ticket_request' && in_array($this->type, ['all', 'list', 'get'])) {
                    return returnResponse($this->responseParser(), 200);
                }
                $this->execQuery();
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
            $this->generateCode();
//            $this->insertUserName();
        } else if ($request['type'] === 'update') {
            $this->conditions = $request['conditions'];
            $this->values = $request['values'];
        } else if (in_array($request['type'], ['delete', 'list', 'get'])) {
            $this->conditions = $request['conditions'];
        }

    }
    private function generateCode(): void
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

        $this->values[$key] = $baseCodePrefix . str_pad((string)++$count, $numDigits ?? 3, "0", STR_PAD_LEFT);

        $this->request->merge(['values' => $this->values]);
    }

//    private function TicketTypes()
//    {
//        $typeQuery = DB::table('ticket_request')
////            ->leftJoin('assignment', 'ticket_request.ticket_code', '=', 'assignment.ticket_code')
//            ->leftJoin('tbl_general_options as type_options', 'ticket_request.type', '=', 'type_options.code')
//            ->leftJoin('tbl_general_options as priority_options', 'ticket_request.priority_level', '=', 'priority_options.code')
//            ->leftJoin('tbl_general_options as status_options', 'ticket_request.status', '=', 'status_options.code')
//            ->select(
//                'ticket_request.*',
//                'type_options.value as type_name',
//                'priority_options.value as priority_name',
//                'status_options.value as status_name'
//            )
//            ->where('type_options.type', 'type')
//            ->where('priority_options.type', 'priority')
//            ->where('status_options.type', 'status');
//
//        // updated condition handler
//        if (!empty($this->conditions)) {
//            foreach ($this->conditions as $field => $value) {
//                if (str_contains($field, '.')) {
//                    $typeQuery->where($field, $value);
//                } else {
//                    $typeQuery->where("ticket_request.$field", $value);
//                }
//            }
//        }

        private function TicketTypes()
    {
        $typeQuery = DB::table('ticket_request')
            ->leftJoin('tbl_general_options as type_options', function($join) {
                $join->on('ticket_request.type', '=', 'type_options.code')
                    ->where('type_options.type', '=', 'type');
            })
            ->leftJoin('tbl_general_options as priority_options', function($join) {
                $join->on('ticket_request.priority_level', '=', 'priority_options.code')
                    ->where('priority_options.type', '=', 'priority');
            })
            ->leftJoin('tbl_general_options as status_options', function($join) {
                $join->on('ticket_request.status', '=', 'status_options.code')
                    ->where('status_options.type', '=', 'status');
            })
            ->select(
                'ticket_request.*',
                'type_options.value as type_name',
                'priority_options.value as priority_name',
                'status_options.value as status_name'
            );

        // updated condition handler
        if (!empty($this->conditions)) {
            foreach ($this->conditions as $field => $value) {
                if (str_contains($field, '.')) {
                    $typeQuery->where($field, $value);
                } else {
                    $typeQuery->where("ticket_request.$field", $value);
                }
            }
        }

        return $typeQuery->get();
    }

    private function responseParser(): mixed
    {
        if ($this->table === 'ticket_request') {
            if ($this->type == 'all' || $this->type == 'get') {
                return $this->TicketTypes();
            }
        }
            return $this->filterResponse($this->response);
    }


//    private function getUser() {
//        $this->user = DB::table('system_users')->where('user_name', Auth::user()->name)->first();
//    }
////    private function insertUserName ()
////    {
////            if (Schema::hasColumn($this->table, 'user_name')) {
////                $this->getUser();
////            }
////        }
//
//    private function insertUserName()
//    {
//        if (Schema::hasColumn($this->table, 'user_name')) {
//            $userData = [
//                'user_name' => Auth::user()->user_name,
//            ];
//            DB::table($this->table)->insert($userData);
//        }
//    }


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
            'sort' => $this->sort ?? null,
            'limit' => $this->limit ?? null,
            'key' => $this->key ?? null,
        ];
    }
}
