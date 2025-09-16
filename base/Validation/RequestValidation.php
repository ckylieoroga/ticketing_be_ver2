<?php

namespace Base\Validation;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class RequestValidation
{
    protected array $param;
    protected string $endpoint;
    protected Response $response;
    protected array $guards;
    protected Request $request;

    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        session()->start();
        $this->request = $request;
        $this->response = $next($request);
        $this->guards = $guards;
        $this->endpoint = $request->path();
        $this->param = $request->all();
        //TODO : Add request logs here
        return $this->requestParamValidation();
    }

    private function requestParamValidation() : Response {
        if($this->endpoint === "gateway/auth") return $this->response;
        if($this->endpoint === "gateway/grequest") return $this->getParamValidation();
        if (Auth::check()) return $this->getParamValidation();
        else return returnResponse(null, 401);
    }

    private function getParamValidation() : Response {
        try{
            $request = $this->request->all();
            if(!isset($request['request'])) return returnResponse(null, 801);
            if(!isset($request['type'])) return returnResponse(null, 802);
            //Check Request Services
            $isRequest = DB::connection('sys_base')->table('request_services')->where('request', $request['request'])
                ->where('type', $request['type'])->first();
            if(!$isRequest) return returnResponse(null, 803);
            else {
                $requestTypeExceptions = $this->requestParamExceptions();
                //return returnResponse($requestTypeExceptions, 802);
                if(!in_array($isRequest->conditions, $requestTypeExceptions)){
                    //Get Request Details from Database
                    $isRequestDetails = DB::connection('sys_base')->table('request_fields')->where('table', $isRequest->table)
                        ->where('query', 'like', '%'.$request['type'].'%')->get();
                    if(sizeof($isRequestDetails) === 0) return returnResponse(null, 804);
                    //Get fields
                    $fields = array();
                    foreach($isRequestDetails as $isRequestDetail){
                        $fields[] = $isRequestDetail->field;
                    }
                    $conditions = explode(',', $isRequest->conditions);
                    foreach($conditions as $key => $value) $conditions[$key] = trim($value);
                    $params = $this->param;
                    $columns = array();
                    //Check if param keys same with fields
                    foreach ($params as $key => $param){
                        if(in_array($key, $conditions)){
                            if($key === 'values'){
                                foreach($isRequestDetails as $isRequestDetail){
                                    $columns[trim($key).'.'.$isRequestDetail->field] = $isRequestDetail->type.'|'.$isRequestDetail->property;
                                }
                            }
                            else if($key === 'conditions'){
                                //Check if param key exist in request fields
                                foreach($isRequestDetails as $isRequestDetail){
                                    foreach($param as $paramKey => $paramValue){
                                        if(in_array($paramKey, $fields)){
                                            if($paramKey === $isRequestDetail->field){
                                                $columns[trim($key).'.'.$paramKey] = $isRequestDetail->type.'|'.$isRequestDetail->property;
                                            }
                                        }
                                        else return returnResponse($paramKey. ' field not found', 805);
                                    }
                                }
                            }
                        }
                    }
                    //Validate fields
                    if(!$this->request->validate($columns)) return returnResponse($this->request->validate($columns), 805);
                }
                return $this->response;
            }
        }
        catch (\Exception $exception){
            return returnResponse($exception->getMessage(), 805);
        }
    }

    private function requestParamExceptions() : array {
        return [
            "none",
            "change",
        ];
    }
}
