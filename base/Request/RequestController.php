<?php

namespace Base\Request;

//use alhimik1986\PhpExcelTemplator\setters\CellSetterArrayValueSpecial;
use App\Http\Controllers\Controller;
use Base\Models\DBQueries;
use Base\Tables\RequestServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Base\Tables\ControllerNamespace;
//define('SPECIAL_ARRAY_TYPE', CellSetterArrayValueSpecial::class);

class RequestController extends Controller
{

    protected Request $request;
    protected ?string $controller;
    protected ?string $function;
    protected ?string $table;
    protected ?DBQueries $query;

    public function login(){
        date_default_timezone_set('Asia/Manila');
        return request();
    }

    public function customRequest (Request $request): ?JsonResponse
    {
        date_default_timezone_set('Asia/Manila');
        $this->request = $request;
        if($this->requestClass()){
            try {
                $controller = $this->controller;
                $function = $this->function;
                return (new $controller)->$function($request);
            }
            catch (\Exception $exception){
                return returnResponse($exception->getMessage(), 400);
            }

        }
        return returnResponse("Request not defined!", 801);
    }

    private function requestClass() : bool {
        $request = $this->request->all();
        $param = RequestServices::query()->where('request',$request['request'])->where('type', $request['type'])->first();
        if($param){
            $this->controller = $this->getNameSpace($param->namespace).$param->controller;
            $this->function = $param->function;
            return true;
        } else return false;
    }

    private function getNameSpace($id) : string {
        $namespace = ControllerNamespace::query()->select()->where('id','=', $id)->first();
        if($namespace)  return str_replace('.', '\\', $namespace->namespace);
        return '';
    }

}
