<?php

namespace Base\Auth;

use Base\Tables\AuthAccessTokens;
use Base\Tables\SystemUsers;
use Base\Tables\UserCredentials;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AuthService
{
    protected $username, $password, $request;

    public function __construct($request){
        date_default_timezone_set("Asia/Manila");
        $this->username = $request->username ?? null;
        $this->password = $request->password ?? null;
        $this->request = $request;
    }

    public function authenticate() : JsonResponse {
        $credentials = $this->verify();
        if(Auth::attempt($credentials)){
            $user = $this->request->user();
            if($this->request->user()->token()) $this->request->user()->token()->revoke();
            $tokenCount = $this->getTokenCount();
            if($tokenCount < $this->getUser()->max_tokens){
                $this->updateLastLogin();
                $tokenResult = $user->createToken('Token');
                $token = $tokenResult->token;
                $token->expires_at = Carbon::now()->addMinute(tokenExpiry());
                $token->save();
                $userDetails = $this->getUser()->toArray();
                $userDetails['user_name'] = $userDetails['custom_user_name'];
                $modules = json_encode($this->getUserModules());
                unset($userDetails['id']);
                unset($userDetails['max_tokens']);
                unset($userDetails['custom_user_name']);
                return returnResponse([
                    'token' => $tokenResult->accessToken,
                    'expiresAt' => Carbon::parse($token->expires_at)->toDateTimeString(),
                    'loggedUser' => $userDetails,
                    'accessedModules' => $modules
                ], 200);
            }
            return returnResponse(["response" => "Maximum number of token reached (".$this->getUser()->max_tokens.")"], 401);
        }
        else { return returnResponse(["response" => "Invalid Credentials"], 401);}
    }
    private function verify() {
        $user['name'] = null;
        $user['password'] = null;
        $userData = SystemUsers::where('custom_user_name', $this->username)->first();
        if($userData){
            $pass =  Crypt::decryptString($userData->secret_key);
            $userData->pass = $pass;
            if($pass == $this->password){
                $user['name'] =  $userData->user_name;
                $user['password'] = $userData->secret_key;
            }
        }
        return $user;
    }

    private function getTokenCount() : int {
        $user = $this->request->user();
        $expireTime = Carbon::now()->format('Y-m-d H:i:s');
        AuthAccessTokens::where('user_id', $user->id)->where('expires_at', '<', $expireTime)->delete();
        return AuthAccessTokens::where('user_id', $user->id)->count();
    }

    public function register() : JsonResponse {
        try {
            $requestData = $this->request;
            $values = $requestData['values'];
            $key = Crypt::encryptString("Bouvet@2023");
            $create = New SystemUsers();
            $create->first_name = $values['firstName'];
            $create->middle_name = $values['middleName'];
            $create->last_name = $values['lastName'];
            $create->user_name = $values['userName'];
            $create->secret_key = $key;
            $create->gender = $values['gender'];
            $create->birthday = $values['birthday'];
            $create->email = $values['email'];
            $create->department = $values['department'];
            $create->employment_status = $values['employmentStatus'];
            $create->max_tokens = 5;
            $create->status = 1;
            $create->save();

            $cred = new UserCredentials();
            $cred->name = $values['userName'];
            $cred->password = bcrypt($key);
            $cred->save();
            return returnResponse($this->getUser(), 200);
        }
        catch (\Exception $exception){
            return returnResponse(["response" => $exception->getMessage()] , 422);
        }

    }

    public function getUser(){
        return SystemUsers::where('user_name', Auth::user()->name)->first();
    }

    public function updateUser(){
        $request = $this->request;
        $conditions = Utility::parseColumns($request['conditions']);
        $values = Utility::parseColumns($request['values']);
        SystemUsers::query()->select()->where($conditions)->update($values);
        return SystemUsers::query()->select()->where($conditions)->get()->first();
    }

    public function changePassword() {
        $response['code'] = 1;
        $response['msg'] = "Invalid Password";
        $request = $this->request;
        $conditions = $request['conditions'];
        $values = $request['values'];
        if($values['newPassword'] === $values['confirmPassword']){
            $userFile = SystemUsers::query()->select('secret_key')->where(['user_name' => $conditions['userName']])->get()->first();
            if($userFile){
                $pass =  Crypt::decryptString($userFile['secret_key']);
                if($pass === $values['oldPassword']){
                    $key = Crypt::encryptString($values['newPassword']);
                    SystemUsers::query()->where(['user_name' => $conditions['userName']])->update(['secret_key' => $key]);
                    UserCredentials::query()->where('name','=',$conditions['userName'])->
                    update(['password' => bcrypt($key)]);
                    $response['code'] = 0;
                    $response['msg'] = "Password Changed Successfully";
                }
            }
        }
        else $response['msg'] = "Password do not match";
        return $response;
    }

    public function getAllUser(){
        return SystemUsers::query()->select()->where('user_name', '!=', Auth::user()->name)->get();
    }

    private function updateLastLogin() : bool {
        return SystemUsers::where('user_name', Auth::user()->name)->update(['last_login' => date('Y-m-d H:i:s')]);
    }
    private function getUserModules(){
        return DB::table('tblMainMenu')->where('useraccess','LIKE',"%".(strtoupper(Auth::user()->user_type."%")))
        ->get()->map(function($data){
            if(!$data) return [];
            return [
                'label'=>$data->description,
                'iconType'=>$data->iconType,
                'icon'=>$data->icon,
                'link'=>$data->linkName
            ];
        });
    }
}
