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

class TicketRequestController extends ParamSetup
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


                // if (!$this->isAuthorized($this->table, $this->type)) {
                //     return returnResponse("Unauthorized access to '{$this->type}' on '{$this->table}'", 403);
                // }
                if ($this->table === 'ticket_request' && in_array($this->type, ['all', 'list', 'get'])) {
                    return returnResponse($this->responseParser(), 200);
                }
                if (in_array($this->request->input("type"), ["add", "update"])) {
                    if (!empty($this->details) && isset($this->details["values"])) {
                        $imageDetail = $this->details["values"];

                        if (!preg_match('/^data:image\/(\w+);base64,/', $imageDetail)) {
                            return response()->json(['error' => 'Invalid photo'], 400);
                        } else {
                            $this->saveDetails2();
                        }
                    }
                    // else: no details provided, just skip saving image
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
            $this->details = $this->values["details"] ?? [];
            unset($this->values["details"]);
            $this->generateCodeGeneric('keyCode', 'code_prefix'); //generate ticket code
            $this->generateCodeGeneric('keyTicket', 'code_prefix_ticket'); // generate ticket number
            $this->code = $this->values["ticket_code"] ?? null;
        } else if ($request['type'] === 'update') {
            $this->conditions = $request['conditions'];
            $this->values = $request['values'];
        } else if (in_array($request['type'], ['delete', 'list', 'get'])) {
            $this->conditions = $request['conditions'];
        }
    }


    private function generateCodeGeneric(string $keyType, string $codePrefixType): void
    {
        $crudDetails = DB::connection("sys_base")
            ->table("crud_table_details")
            ->where("module", $this->request["request"])
            ->get();

        if ($crudDetails->isEmpty()) return;

        $key = $crudDetails->first(fn($cd) => $cd->type === $keyType)?->value ?? null;
        if (is_null($key)) throw new Exception("Setup Controller requires $keyType in crud_table_details");
        if (!Schema::hasColumn($this->table, $key)) throw new Exception("There is no $key in $this->table");

        $codePrefix = $crudDetails->first(fn($cd) => $cd->type === $codePrefixType)?->value ?? null;
        if (is_null($codePrefix)) throw new Exception("Setup Controller requires $codePrefixType");

        $dateFormat = $crudDetails->first(fn($cd) => $cd->type === "date_format")?->value ?? null;
        $numDigits = $crudDetails->first(fn($cd) => $cd->type === "num_digits")?->value ?? null;

        $baseCodePrefix = $codePrefix . date($dateFormat ?? "Y");
        $count = DB::table($this->table)
            ->where($key, "like", $baseCodePrefix . "%")
            ->count();

        $this->values[$key] = $baseCodePrefix . str_pad((string)++$count, $numDigits ?? 3, "0", STR_PAD_LEFT);

        $this->request->merge(['values' => $this->values]);
    }

    private function saveDetails2()
    {
        foreach ($this->details as $key => $object) {
            if (is_array($object)) {
                /* foreach ($object as $key2 => $object2) { */
                /*     if (is_array($object2)) { */
                /*         foreach ($object2 as $key3 => $value) { */
                /*             $this->saveDetail($key3, $value, "dbInsert", $key, $key2); */
                /*         } */
                /*     } else { */
                /*         $this->saveDetail($key2, $object2, "dbInsert", $key); */
                /*     } */
                /* } */
            } else {
                $this->saveDetail2($key, $object, $this->code, "image");
            }
        }
    }

    private function saveDetail2($type, $value, $code, $category)
    {
        $values = [];
        $values["values"] = $value;
        $values["ticket_code"] = $code;
        $values["category"] = $category;
        $values["type"] = 'image_proof';
        $values["status"] = 1;


        if (!preg_match('/^data:image\/(\w+);base64,/', $value)) {
            return response()->json(['error' => 'Invalid photo'], 400);
        } else return (new DBQueries("ticket_details", $values))->dbInsert();
    }


    private function isAuthorized(string $table, string $type)
    {
        $user = Auth::user();
        $userReg = SystemUsers::where('user_name', $user->name)->first();
        if (!$userReg) return false;

        // get specific ticket access (must be assigned)
        if ($type === 'get' && $table === 'ticket_request') {
            $ticketCode = $this->request['conditions']['ticket_code'] ?? null;
            if (!$ticketCode) return false;

            $result = DB::table('ticket_request AS t')
                ->leftJoin('tbl_assignment_personnel AS a', 't.ticket_code', '=', 'a.ticket_code')
                ->where('t.ticket_code', $ticketCode)
                ->where('a.assigned_to', $userReg->user_name)
                ->select('t.ticket_code')
                ->first();

            return (bool)$result;
        }

        // list tickets (auto filter to assigned)
//        if ($type === 'all' && $table === 'ticket_request') {
//            $assign = DB::table('tbl_assignment_personnel')
//                ->where('tbl_assignment_personnel.assigned_to', $userReg->user_name)
//                ->first();
//
//            return $assign;
//        }
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
        $user = Auth::user();
        $userReg = SystemUsers::where('user_name', $user->name)->first();
        
        $typeQuery = DB::table('ticket_request')
            ->leftJoin('tbl_assignment_personnel as a', 'ticket_request.ticket_code', '=', 'a.ticket_code')
            ->leftJoin('tbl_general_options as type_options', function($join) {
                $join->on('ticket_request.type', '=', 'type_options.code')
                    ->where('type_options.type', '=', 'ticket_type');
            })
            ->leftJoin('tbl_general_options as priority_options', function($join) {
                $join->on('ticket_request.priority_level', '=', 'priority_options.code')
                    ->where('priority_options.type', '=', 'priority');
            })
            ->leftJoin('tbl_general_options as status_options', function($join) {
                $join->on('ticket_request.status', '=', 'status_options.code')
                    ->where('status_options.type', '=', 'ticket_status');
            })
            ->leftJoin('system_users as users', function($join) {
                $join->on('ticket_request.user_name', '=', 'users.user_name')
                    ->where('users.status', '=', '1');
            })
            ->select(
                'ticket_request.*',
                'type_options.value as type_name',
                'priority_options.value as priority_name',
                'status_options.value as status_name',
                'users.custom_user_name as custom_user_name'
            );


        if ($userReg->user_type === 'AD') {
            $typeQuery->where('a.assigned_to', $userReg->user_name);
        } elseif ($userReg->user_type === 'CL') {
            $typeQuery->where('ticket_request.user_name', $userReg->user_name);
        }

        return $typeQuery->get();
    }

    private function responseParser(): mixed
    {
        if ($this->table === 'ticket_request') {
            if ($this->type == 'all') {
                return $this->TicketTypes();
            }
        }
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
            'sort' => $this->sort ?? null,
            'limit' => $this->limit ?? null,
            'key' => $this->key ?? null,
        ];
    }
}
