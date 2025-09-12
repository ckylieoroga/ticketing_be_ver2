<?php

namespace Base\Models;

use Base\Tables\SystemLogs;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class Logs
{
    protected $request, $data, $endpoint;
    protected ?string $type;

    public function __construct($data, $type){
        $this->request = $data;
        $this->type = $type;
        $this->endpoint = request()->path();
    }

    public function addLog() : string {
        date_default_timezone_set('Asia/Manila');
        $data = $this->parseData();
        if(gettype($data['value']) === 'string'){
            if(!str_contains($data['value'], "System Login")){
                if($this->type === 'request') $data['value']['requestId'] = session()->getId();
            }
        }
        else if($this->type === 'request') $data['value']['requestId'] = session()->getId();

//        if($data['value'] !== "System Login: ".Auth::user()->name){
//            if($this->type === 'request') $data['value']['requestId'] = session()->getId();
//        }
        $logs = new SystemLogs();
        $logs->user_id = $data['user'];
        $logs->endpoint = $data['endpoint'];
        $logs->type = $data['type'];
        $logs->value = json_encode($data['value']);
        $logs->save();
        $this->createLog();
        return "Log created";
    }

    private function parseData() : array {
        $data = $this->request;
        $response['user'] = Auth::user() ? Auth::user()->id : 0;
        $response['type'] = $this->type;
        $response['endpoint'] = $this->endpoint ?? null;
        if($this->type === 'request'){
            if(isset($data['username'])) $response['value'] = "System Login: ".$data['username'];
            else $response['value'] = $data;
        }
        else if($this->type === 'response'){
            $response['value'] = $data;
        }
        return $response;
    }

    private function createLog() {
//        $logCount = SystemLogs::query()->select()->count();
//        $logFile = '';
//        if($logCount > 50){
//            $logs = SystemLogs::query()->select()->get();
//            foreach($logs as $log){
//                $file = $log->created_at.' | '.$log->user_id.' | '.$log->type.' | '.$log->endpoint.' | '.$log->value.' '.PHP_EOL;
//                $logFile = $logFile.$file;
//            }
//            file_put_contents(public_path()."/tmp/Log_".date('Y-m-d H:i:s'), $logFile);
//            SystemLogs::query()->truncate();
//        }
    }

    public function getLogs($string, $transType, $type) {
        $data = SystemLogs::query()
            ->where('value','like','%'.$string.'%')
            ->where('value','like','%"request":"'.$transType.'"%')->orderByDesc('created_at')->get();
        $data = $data->map(function ($value){
            $string = json_decode($value->value, true);
            $user = DB::table('system_users')->where('id', $value->user_id)->first();
            return [
                'data' => $string,
                'action' => $string['type'],
                'request' => $string['request'],
                'userName' => $user ? $user->user_name: $value->user_id,
                'userFullName' => $user ? $user->first_name.' '.$user->last_name: $value->user_id,
                'dateTime' => Carbon::parse($value->created_at)->format('d-M-Y h:i:s A'),
            ];
        });
        $return = array();
        foreach($data as $value){
            if($value['action'] !== 'get') {
                $return[] = $value;
            }
        }
        $count = sizeof($return);
        if((int)$type === 0) {
            $data = $return;
            $return = array();
            foreach($data as $key => $value){
                if($key < 3){
                    $return[] = $value;
                }
            }
        }
        $data = array();
        $data['list'] = $return;
        $data['count'] = $count;
        return $data;
    }

    private function parseTransactionData() : array {
        $data = $this->request;
        $response['module'] = $data['module'];
        $response['request'] = $data['request'];
        $response['type'] = $data['type'];
        $response['param'] = $data['param'];
        $response['original_data'] = '';
        $response['amended_data'] = '';
        $response['user'] = Auth::user()->name;
        $response['created_at'] = date('Y-m-d H:i:s');
        $response['updated_at'] = date('Y-m-d H:i:s');
        if($data['type'] === 'update'){
            $original = array();
            $amendment = array();
            $originalData = $data['original_data'];
            $amendedData = $data['amended_data'];
            foreach ($amendedData as $key => $value){
                if(isset($originalData[$key])){
                    $original[$key] = $originalData[$key];
                    $amendment[$key] = $value;
                }
            }
            $response['original_data'] = $original;
            $response['amended_data'] = $amendment;
        }
        return $response;
    }

    public function addTransactionLog() : string {
        date_default_timezone_set('Asia/Manila');
        $data = $this->parseTransactionData();
        DB::connection('transaction_logs')->table('tbl_transaction_logs')->insert($data);
        return "Log created";
    }

    public function updateJournalCreatedBy(){
        $users = $this->getUserNames();
        $journals = DB::table('tbl_journal')->whereNull('created_by')->get();
        $counter = 0;
        echo sizeof($journals);
        foreach($journals as $journal){
            $data = SystemLogs::query()
                ->where('value','like','%'.trim($journal->particulars).'%')
                ->where('value','like','%"request":"'.$journal->type.'","type":"add"%')->get();
            if($data){
                foreach($data as $value){
                    echo '['.$counter.'] '.$journal->type.' '.$journal->code.' '.$users[$value->user_id].PHP_EOL;
                    DB::table('tbl_journal')->where('code', $journal->code)->whereNull('created_by')->update(['created_by' => $users[$value->user_id]]);
                    $counter = $counter + 1;
                }
            }
            $journalType = $journal->type === 'advances' ? 'cash_advance' : ($journal->type === 'liquidation' ? 'branch_liquidation' : $journal->type);
            $data2 = SystemLogs::query()
                ->where('value','like','%'.trim($journal->reference_number).'%')
                ->where('value','like','%"request":"'.$journalType.'","type":"add"%')->get();
            if($data2){
                if(sizeof($data2) > 0){
                    foreach($data2 as $value){
                        echo '['.$counter.'] '.$journal->type.' '.$journal->code.' '.$users[$value->user_id].PHP_EOL;
                        DB::table('tbl_journal')->where('code', $journal->code)->whereNull('created_by')->update(['created_by' => $users[$value->user_id]]);
                        $counter = $counter + 1;
                    }
                }
                else {
                    $this->getReferenceNumberDisbursement($journal);
                }
            }
        }
        echo "END OF FILE";
    }

    public function getUserNames() : array {
        $users = DB::table('system_users')->select('id','user_name')->get();
        $response = array();
        foreach($users as $user){
            $response[$user->id] = $user->user_name;
        }
        return $response;
    }

    public function getReferenceNumberDisbursement($journal){
        $counter = 0;
        $users = $this->getUserNames();
        $data2 = SystemLogs::query()
            ->where('value','like','%'.trim($journal->reference_number).'%')
            ->where('value','like','%"request":"disbursement"%')->first();
        $value = $data2?->value;
        if($value) {
            $data = json_decode($value, true);
            if(isset($data['values']['details']['accounts'])){
                $codeData =  $data['values']['details']['accounts'];
                foreach($codeData as $code){
                    if(isset($code['reference_number'])){
                        echo $code['reference_number'].PHP_EOL;
                        $file = SystemLogs::query()
                            ->where('value','like','%'.$code['reference_number'].'%')
                            ->where('value','like','%"request":"disbursement","type":"add"%')->get();
                        if($file){
                            foreach($file as $value){
                                echo '['.$counter.'] '.$journal->type.' '.$journal->code.' '.$users[$value->user_id].PHP_EOL;
                                DB::table('tbl_journal')->where('code', $journal->code)->whereNull('created_by')->update(['created_by' => $users[$value->user_id]]);
                                $counter = $counter + 1;
                            }
                        }
                    }
                }

            }
        }
        else echo 'none';

    }
}
