<?php

namespace App\Http\Controllers;

use Base\Tables\SystemUsers;
use Exception;
use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommentController extends ParamSetup
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
                $this->execQuery();
                $this->isInternal();

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

        } else if ($request['type'] === 'update') {
            $this->conditions = $request['conditions'];
            $this->values = $request['values'];
        } else if ($request['type'] === 'get') {
            $this->conditions = $request['conditions'];
//            $this->isAuthorized($this->table, $this->type);
        } else if (in_array($request['type'], ['delete', 'list'])) {
            $this->conditions = $request['conditions'];
        }

    }

    private function isAuthorized(string $table, string $type)
    {
        $user = Auth::user();
        $userReg = SystemUsers::where('user_name', $user->name)->first();
        if (!$userReg) return false;

        // allow SP users to view comments without assignment
        if ($type === 'list') {
            if ($userReg->user_type === 'SP') {
                $support = DB::table('tbl_comment')
                    ->where($this->conditions)
                    ->get();

                return $support;
            }

            if ($userReg->user_type === 'DV') {
                $ticketCode = $this->request['conditions']['ticket_code'] ?? null;

                if (!$ticketCode) {
                    return false;
                }

                $developerComments = DB::table('tbl_comment AS t')
                    ->leftJoin('tbl_assignment_personnel AS a', 't.ticket_code', '=', 'a.ticket_code')
                    ->leftJoin('system_users AS u', 't.created_by', '=', 'u.custom_user_name')
                    ->where('t.ticket_code', $ticketCode)
                    ->where('a.assigned_to', $userReg->user_name)
                    ->where('t.is_internal', 1)
                    ->where(function ($query) use ($userReg) {
                        $query->where('t.created_by', $userReg->custom_user_name)
                            ->orWhere('u.user_type', 'SP');
                    })
                    ->select('t.*')
                    ->get();

                return $developerComments;
            }

            if ($userReg->user_type === 'CL') {
                $ticketCode = $this->request['conditions']['ticket_code'] ?? null;
                if (!$ticketCode) return false;

                // check if the ticket belongs to the client
                $isOwner = DB::table('ticket_request')
                    ->where('ticket_code', $ticketCode)
                    ->where('user_name', $userReg->user_name)
                    ->exists();

                if (!$isOwner) return false;

              // own comments OR SP comments
                $client = DB::table('tbl_comment AS t')
                    ->leftJoin('system_users AS u', 't.created_by', '=', 'u.custom_user_name')
                    ->where('t.ticket_code', $ticketCode)
                    ->where('t.is_internal', 0)
                    ->where(function ($query) use ($userReg) {
                        $query->where('t.created_by', $userReg->custom_user_name)
                            ->orWhere('u.user_type', 'SP');
                    })
                    ->select('t.*')
                    ->get();

                return $client;
            }
        }
    }

    public function isInternal () : void
    {
        $user = Auth::user();
        if ($user) {
            $userReg = SystemUsers::where('user_name', $user->name)->first();
            if ($userReg) {
                if ($userReg->user_type === 'DV') {
                    $this->values['is_internal'] = 1;

                }
            }
        }
    }
    private function responseParser(): mixed
    {
        if ($this->table === 'tbl_comment') {
            if ($this->type == 'list') {
                return $this->isAuthorized($this->table, $this->type);
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
