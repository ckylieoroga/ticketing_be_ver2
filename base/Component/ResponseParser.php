<?php

namespace Base\Component;

use Illuminate\Support\Facades\DB;

class ResponseParser
{

    protected int $code;
    public function __construct($code) {
        $this->code = (int)$code;
    }

    public function response() : array {
        $response = $this->responseMessages($this->code);
        return [
            'code' => $response['code'],
            'responseCode' => $this->code,
            'message' => $response['msg'],
            'httpCode' => $response['httpCode']
        ];
    }

    private function responseMessages($code) : array {
        $responseDetails = DB::connection('sys_base')->table('error_codes')->get();
        $responses = array();
        foreach($responseDetails as $responseDetail){
            $responses[$responseDetail->code] = $responseDetail->message;
        }
        $httpCode = $code;
        if($code > 500) $httpCode = 400;
        if(isset($responses[$code])) return ["msg" => $responses[$code], "httpCode" => $httpCode, "code" => $code === 200 ? 0 : 1];
        else return ["msg" => "Response code not found", "httpCode" => $httpCode, "code" => 1];
    }

}
