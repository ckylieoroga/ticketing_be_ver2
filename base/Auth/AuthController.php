<?php

namespace Base\Auth;

use Base\Auth\AuthService;
use App\Http\Controllers\Controller;
use Base\Tables\SystemUsers;
use Base\Tables\UserCredentials;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as JRes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use Base\ExternalApi\CurrencyApi;

class AuthController extends Controller
{
    public function auth(Request $request) : JRes {
        $auth = New AuthService($request);
        return $auth->authenticate();
    }

    public function registerAdmin() : JRes {
        $credentials = $this->parseUserCredentials('Qbs@2025');
        $create = New SystemUsers();
        $create->first_name = env('ADMIN_FIRST_NAME','admin');
        $create->last_name = env('ADMIN_LAST_NAME','admin');
        $create->user_name = $credentials['userName'];
        $create->custom_user_name = env('ADMIN_USERNAME','admin2');
        $create->gender = env('ADMIN_GENDER','M');
        $create->birthday = env('ADMIN_BIRTHDATE','2002-06-22');
        $create->email = env('ADMIN_EMAIL','hidlaojayvee@gmail.com');
        $create->secret_key = $credentials['userKey'];
        $create->user_type = "DV"; # temporary type
        $create->designation = 0;
        $create->max_tokens = 999;
        $create->status = 1;
        $create->save();

        $cred = new UserCredentials();
        $cred->name = $credentials['userName'];
        $cred->password = $credentials['userPassword'];
        $cred->save();

        return response()->json('register', 200);

    }

    private function parseUserCredentials($userPass) : array {
        $baseSystem = env('USER_CRED_BASE');
        $baseDomain = env('USER_CRED_DOMAIN');
        $count = DB::table('system_users')->count();
        $key = Crypt::encryptString($userPass);
        $return['userName'] = $baseSystem.$count;
        $return['userEmail'] = $baseSystem.$count.'@'.$baseDomain;
        $return['userKey'] = $key;
        $return['userPassword'] = bcrypt($key);
        return $return;
    }

    public function userRegistration(Request $request) : JRes {
        $pass = $this->generateDefaultPassword(8);
        $user = $request->input("values");
        try {
            $credentials = $this->parseUserCredentials($pass);

            $create = new SystemUsers();
            $create->first_name = $user['firstName'];
            $create->middle_name = $user['middleName'] ?? '';
            $create->last_name = $user['lastName'];
            $create->user_name = $credentials['userName'];
            $create->custom_user_name = $user['userName'];
            $create->secret_key = $credentials['userKey'];
            $create->suffix = $user['suffix'] ?? null;
            $create->gender = $user['gender'];
            $create->birthday = $user['birthday'];
            $create->email = $user['email'];
            $create->department = $user['department'];
            $create->designation = $user['designation'] ?? 1;
            $create->employment_status = $user['employmentStatus'];
            $create->max_tokens = 999;
            $create->status = $user['status'] ?? 1;
            $create->user_type = 'CL';

            $create->save();

            $cred = new UserCredentials();
            $cred->name = $credentials['userName'];
            $cred->password = $credentials['userPassword'];
            $cred->save();

            return returnResponse(["password" => $pass], 200);

        } catch (\Exception $e) {
            return returnResponse($e->getMessage(), 422);
        }
    }

    public function register(Request $request) : JRes {
        $auth = New AuthService($request);
        return $auth->register();
    }

    public function registerUser($fname, $lname, $user, $pass) : JRes {
        $credentials = $this->parseUserCredentials($pass);
        $create = New SystemUsers();
        $create->first_name = $fname;
        $create->last_name = $lname;
        $create->custom_user_name = $user;
        $create->user_name = $credentials['userName'];
        $create->gender = 'M';
        $create->birthday = '2018-10-18';
        $create->email = $user.'@accounting.com';
        $create->secret_key = $credentials['userKey'];
        $create->designation = 0;
        $create->max_tokens = 5;
        $create->status = 1;
        $create->save();

        $cred = new UserCredentials();
        $cred->name = $credentials['userName'];
        $cred->password = $credentials['userPassword'];
        $cred->save();

        return response()->json('register', 200);

    }

    public function resetPassword($username) : JRes {
        try{
            $user = DB::table('system_users')->where('custom_user_name', $username)->first();
            if($user){
                $systemUser = $user->system_user_name;
                $newPassword = $this->generateDefaultPassword(8);
                $key = Crypt::encryptString($newPassword);
                DB::table('system_users')->where('user_name', $systemUser)->update(['secret_key' => $key, 'last_login' => null]);
                $password = bcrypt($key);
                DB::table('user_credentials')->where('name', $systemUser)->update(['password' => $password]);
                return returnResponse( $newPassword, 200);
            }
            return returnResponse(["msg" => "No User Found!"], 422);
        }
        catch (\Exception $e){
            return returnResponse(["msg" => $e->getMessage()], 422);
        }
    }

    public function resetAllPassword() : JRes {
        try{
            $user = DB::table('system_users')->get();
            if($user){
                $newPassword = 'FTLPAcctg@2025';
                foreach($user as $username){
                    $key = Crypt::encryptString($newPassword);
                    DB::table('system_users')->where('user_name', $username->user_name)->update(['secret_key' => $key, 'last_login' => null]);
                    $password = bcrypt($key);
                    DB::table('user_credentials')->where('name', $username->user_name)->update(['password' => $password]);

                }
                return returnResponse($newPassword, 200);
            }
            return returnResponse(["msg" => "No User Found!"], 422);
        }
        catch (\Exception $e){
            return returnResponse( ["msg" => $e->getMessage()], 422);
        }
    }

    public function changePassword($username, $oldPass, $newPass) : JRes {
        try{
            $user = DB::table('system_users')->where('custom_user_name', $username)->first();
            if($user){
                $systemUser = $user->system_user_name;
                $currentPass = Crypt::decryptString($user->secret_key);
                if($currentPass === $oldPass){
                    $key = Crypt::encryptString($newPass);
                    DB::table('system_users')->where('user_name', $systemUser)->update(['secret_key' => $key, 'last_login' => null]);
                    $password = bcrypt($key);
                    DB::table('user_credentials')->where('name', $systemUser)->update(['password' => $password]);
                    return returnResponse('password changed successfully', 200);
                }
                else return returnResponse(["msg" => "Invalid password"], 422);
            }
            else return returnResponse( ["msg" => "User does not exist"], 422);
        }
        catch (\Exception $e){
            return returnResponse( ["msg" => $e->getMessage()], 422);
        }
    }

    public function checkPassword($username, $oldPass) : JRes {
        try{
            $user = DB::table('system_users')->where('custom_user_name', $username)->first();
            if($user){
                $currentPass = Crypt::decryptString($user->secret_key);
                if($currentPass === $oldPass) return returnResponse( 'password matched', 200);
                else return returnResponse(["msg" => "Invalid password"], 422);
            }
            else return returnResponse(["msg" => "User does not exist"], 422);
        }
        catch (\Exception $e){
            return returnResponse( ["msg" => $e->getMessage()], 422);
        }
    }

    public function passwordList() {
        $user = DB::table('system_users')->get();
        $user->map(function ($user){
            $key = Crypt::decryptString($user->secret_key);
            echo $user->user_name.' = '.$key.PHP_EOL;
        });
    }

    public function reloadPasswords($username) {
        if($username === 'all'){
            $user = DB::table('system_users')->get();
            $user->map(function ($user){
                $pass = Crypt::decryptString($user->secret_key);
                $key = Crypt::encryptString($pass);
                DB::table('system_users')->where('user_name', $user->user_name)->update(['secret_key' => $key, 'last_login' => null]);
                $password = bcrypt($key);
                DB::table('user_credentials')->where('name', $user->user_name)->update(['password' => $password]);
                echo $user->user_name.' = '.$pass.PHP_EOL;
            });
        }
        $user = DB::table('system_users')->where('user_name', $username)->first();
        if($user){
            $pass = Crypt::decryptString($user->secret_key);
            $key = Crypt::encryptString($pass);
            DB::table('system_users')->where('user_name', $username)->update(['secret_key' => $key, 'last_login' => null]);
            $password = bcrypt($key);
            DB::table('user_credentials')->where('name', $username)->update(['password' => $password]);
            echo $user->user_name.' = '.$key.PHP_EOL;
        }
        return $user;
    }

    private function generateDefaultPassword($length_of_string) : string {
        $upperStrings = rand(1,$length_of_string - 2);
        $length_of_string = $length_of_string - $upperStrings;
        $lowerString = rand(1,$length_of_string - 1);
        $numberString = $length_of_string - $lowerString;
        $passKey = $this->getStrings(0,$upperStrings).$this->getStrings(1,$lowerString).$this->getStrings(2,$numberString);
        return str_shuffle($passKey);
    }

    private function getStrings($type, $count){
        $types = ["ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz","0123456789"];
        return substr(str_shuffle($types[$type]), 1, $count);
    }

    public function getUserPasswordList() {
        $data = array();
        $users = DB::table('system_users')->get();
        foreach($users as $user){
            $pass = Crypt::decryptString($user->secret_key);
            $data[$user->user_name] = $pass;
        }
        file_put_contents(public_path()."/userPasswordList.json", json_encode($data));
        return $data;
    }

    public function restoreUserPassword($filename){
        $data = json_decode(file_get_contents(public_path().'/'.$filename), true);
        foreach($data as $user => $pass){
            $key = Crypt::encryptString($pass);
            DB::table('system_users')->where('user_name', $user)->update(['secret_key' => $key]);
            $password = bcrypt($key);
            DB::table('user_credentials')->where('name', $user)->update(['password' => $password]);
        }
        return "Restore Done!";
    }

    public function getCurrencies() : array {
        $currencies = DB::table('tbl_general_options')->where('type','currency')
            ->whereNot('value', 'PHP')->get();
        $data = array();
        foreach($currencies as $currency){
            $convert = new CurrencyApi();
            $conversion = $convert->latest(['base_currency' => $currency->value, 'currencies' => 'PHP']);
            $data[$currency->value] = $conversion['data']['PHP'] ?? 1;
        }
        return $data;
    }

    public function currencies() : array {
        $isExist = DB::table('tbl_foreign_exchange')->where('conversion_date', date('Y-m-d'))->count();
        if($isExist === 0){
            $currencies = $this->getCurrencies();
            foreach($currencies as $key => $currency){
                DB::table('tbl_foreign_exchange')
                    ->insert([
                        'currency' => $key,
                        'amount' => $currency,
                        'conversion_date' => date('Y-m-d'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
            }
        }
        $rates = DB::table('tbl_foreign_exchange')->where('conversion_date', date('Y-m-d'))->get();
        $return = array();
        foreach($rates as $rate){
            $return[$rate->currency] = $rate->amount ? floatval($rate->amount) : 1;
        }
        return $return;
    }
}
