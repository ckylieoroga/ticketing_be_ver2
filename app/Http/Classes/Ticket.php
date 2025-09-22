<?php

namespace App\Http\Classes;

use Base\Models\ParamSetup;
use Base\Tables\RequestServices;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class Ticket{
   public function generateTicketCode($request,$table){
        $crudDetails = DB::connection("sys_base")->table("crud_table_details")->where("module", $request)->get();
        if ($crudDetails->isEmpty()) return;
        
        $key = $crudDetails->first(fn($cd) => $cd->type === "key")?->value ?? null;
     
        if (is_null($key)) throw new Exception("Setup Controller requires key type in crud_table_details");
        if (!Schema::hasColumn($table, $key)) throw new Exception("There is no $key in $table");

        $codePrefix = $crudDetails->first(fn($cd) => $cd->type === "code_prefix")?->value ?? null;
        if (is_null($codePrefix)) throw new Exception("Setup Controller requires code_prefix");

        $dateFormat = $crudDetails->first(fn($cd) => $cd->type === "date_format")?->value ?? null;
        $numDigits = $crudDetails->first(fn($cd) => $cd->type === "num_digits")?->value ?? null;

        $baseCodePrefix = $codePrefix . date($dateFormat ?? "Y");
        $count = DB::table($table)
            ->where($key, "like", $baseCodePrefix . "%")
            ->count();

            // ID
            // TICKET-COUNT
        $code = $baseCodePrefix . str_pad((string) ++$count, $numDigits ?? 3, "0", STR_PAD_LEFT);
        return [ $code , $count ];
    }
}

