<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Device;
use App\Models\PasswordReset;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use Config;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Mail;
use DB;
use ArrayObject;
class AuthController extends Controller
{

    use RestApi, SendsPasswordResetEmails;
    // @var Array $http_codes
    // This protected member contains Http Status Codes
    protected $http_codes = [];

    public $successStatus = 200;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {        
        // Init security library object
        $this->security_lib_obj = new SecurityLib();  
        $this->http_codes = $this->http_status_codes();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("Auth::index");
    }

    /**
     * @apiDesc This webservice enable user to login
     * @apiParam string $mobile required  | Mobile number of passenger  
     * @apiParam string $email  required         | email of passenger
     * @apiParam password $password  required         | password of passenger
     * @apiParam string $resource_type  | ('web', 'ios', 'android') From where data is coming 
     * @apiErr 422 | Validation errors
     * @apiErr 403 | Unauthorized access
     * @apiResp 200 | Whatever message is send from backend on sucess
     */ 
    public function post_login(Request $request)
    {
        $request_data =$this->security_lib_obj->decryptInput($request);
        
        //Check user login from Mobile or Email
        if(!empty($request_data['mobile'])) {
            $input_column_name = 'mobile';
            $input_column_value = $request_data['mobile'];
        }else {
            $input_column_name = 'email';
            $input_column_value = $request_data['email'];
        }

        // Validate request
        $validate = $this->login_validator($request_data);
        if($validate["error"]) {
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('Auth::messages.validation_failed'), 
                    $this->http_codes['HTTP_OK']
                  ); 
        }
        $user_details = array();
        if(Auth::Attempt([$input_column_name => $input_column_value, 'password' => $request_data['password']])) {
            //Create Token for login user
            $user = Auth::user();
            $user_id = $user->user_id;   

            $user_details = User::select('user_id', 'name', 'picture', 'mobile','user_type', 'email')
                            ->where('user_id', '=', $user_id)
                            ->where('is_deleted', '=', 0)
                            ->first()
                            ->toArray();

            $user_details['students'] = Student::select('students.student_id', 'name', 'student_picture')
                            ->join('student_parents', 'student_parents.student_id','=','students.student_id')
                            ->where('student_parents.user_id', '=', $user_id)
                            ->where('students.is_deleted', '=', 0)
                            ->get()
                            ->toArray();
            if (!empty($user_details['students'])) {
                foreach ($user_details['students'] as $key => $value) {
                    if (!empty($value['student_picture'])) {

                        $user_details['students'][$key]['student_picture'] = env('EDUCATION_APP_URL').'/api/media/student_picture/'.$value['student_picture'];
                    } else {
                        $user_details['students'][$key]['student_picture'] = env('EDUCATION_APP_URL').'/api/media/students/default_user.png';
                    }
                }
            }
            if(!empty($user_details['picture'])) {
                $user_details['picture'] = env('EDUCATION_APP_URL').'/api/media/user_picture/'.$user_details['picture'];
            } else {
                $user_details['picture'] = "";
            }
            $user_details['token'] = $user->createToken('Education system')->accessToken;

            //Device table insert and update data
            $user_agent = $request->server('HTTP_USER_AGENT');
            if(!empty($request_data['device_token']))
            {
                $device = new Device;
                $check_device = $device->where([
                                        ['device_token' ,'=', $request_data['device_token']],
                                        ['user_id' ,'=', $user_id]
                                        ])->count();
                if($check_device == 0){
                    $device->device_token = $request_data['device_token'];
                    $device->device_type = strtolower($request_data['device_type']);
                    $device->os_version = $request_data['os_version'];
                    $device->device_model = $request_data['device_model'];
                    $device->created_by = $user_id;
                    $device->updated_by = $user_id;
                    $device->user_id = $user_id;
                    $device->resource_type = strtolower($request_data['resource_type']);
                    $device->user_agent = $user_agent;
                    $device->ip_address = $request->ip();
                    $device->save();
                }
            }

            return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    [$user_details], 
                    [],
                    trans('Auth::messages.login_successfull'), 
                    $this->http_codes['HTTP_OK']
                  );
        }else {
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    [],
                    trans('Auth::messages.login_failed'), 
                    $this->http_codes['HTTP_OK']
                  );
        }     
    }

   /**
    * @DateOfCreation        17 July 2018
    * @ShortDescription      This function is responsible for validating for login
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function login_validator(array $data)
    {
        
        $error = false;
        $errors = [];
        
        if(!empty($data['mobile'])) {
            $validator = Validator::make($data, [
                'mobile' => 'required',
                'password' => 'required',
            ]);
        }else {
            $validator = Validator::make($data, [
                'email' => 'required',            
                'password' => 'required',
            ]);
        }
        
        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }
    
   /**
    * @DateOfCreation        17 July 2018
    * @ShortDescription      Get a validator for an incoming User request
    * @param                 \Illuminate\Http\Request  $request
    * @return                \Illuminate\Contracts\Validation\Validator
    */
    protected function forgot_validations($request_data){
        $errors         = [];
        $error          = false;
        $validation_data = [];
        
        // Check the login type is Email or Mobile
        if(empty($request_data['email'])){
            $validation_data = [
                'mobile' => 'required|max:10',
            ];
        }else{
            $validation_data = [
                'email' => 'required|email|max:150',
            ];
        }
        $validator  = Validator::make(
            $request_data,
            $validation_data
        );
            if($validator->fails()){
                $error  = true;
                $errors = $validator->errors();
            }
        return ["error" => $error,"errors"=>$errors];
    }

   /**
    * @DateOfCreation        17 July 2018
    * @ShortDescription      This function is responsible for generate and get Reset Password 
    * @param                 Array $request   
    * @return                Array of status and message
    */
    public function get_reset_password(Request $request)
    {
        $request_data =$this->security_lib_obj->decryptInput($request);
        $validate    =  $this->forgot_validations($request_data);
        if($validate["error"]){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $validate['errors'],
                trans('Auth::messages.user_validation_error'), 
                $this->http_codes['HTTP_OK']
            ); 
        }
        // Check the login type is Email or Mobile
        if(empty($request_data['email'])){
            $login_key = "mobile";
            $login_value = $request_data['mobile'];
        }else{
            $login_key = "email";
            $login_value = $request_data['email'];
        }
        
        $user = User::where($login_key, $login_value)->first();
        
        if (!$user) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                ["email" => [trans('Auth::messages.not_found')]],
                trans('Auth::messages.not_found'), 
                $this->http_codes['HTTP_NOT_FOUND']
            );
        }
        //Generate random password
        $password =$this->security_lib_obj->genrateRandomPassword();
        $user->password = bcrypt($password);
        $user->save();

        // Send password by email
        $sent = Mail::send('emails.forgotPassword', ['name' => $user->name, 'password' => $password], function($message) use ($user) {
          $message->from(Config::get('constants.MAIL_FROM'), 'Education');
          $message->to($user->email);
          $message->subject(trans('Auth::messages.reset_password_subject'));
        });

        return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('Auth::messages.password_sent'),
                $this->http_codes['HTTP_OK']
            );
    }


   /**
    * @DateOfCreation        17 July 2018
    * @ShortDescription      This function is responsible to delete access token for current user
    * @param                 String $request
    * @return                Array of status and message
    */
    public function logout(Request $request)
    {
        $request_data =$this->security_lib_obj->decryptInput($request);
        $error = false;
        $validator = Validator::make($request_data, [
            'user_id' => 'required'            
        ]);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        if($error == true){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Auth::messages.user_id'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user  = User::find($request_data['user_id']);

        if($user){
            $whereData   =  [
                                'user_id'=> $request_data['user_id'],
                                'device_token'=> $request_data['device_token']
                            ];
            $check_user_token = Device::where($whereData)->delete();
          $user->oauth_access_token()->delete();
          return $this->resultResponse(
              Config::get('restresponsecode.SUCCESS'), 
              [], 
              [],
              trans('Auth::messages.logged_out'),
              $this->http_codes['HTTP_OK']
          );
        }
        return $this->resultResponse(
          Config::get('restresponsecode.ERROR'), 
          [], 
          ["user" => [trans('Auth::messages.not_found')]],
          trans('Auth::messages.not_found'), 
          $this->http_codes['HTTP_NOT_FOUND']
        );

    }

   /**
    * @DateOfCreation        17 July 2018
    * @ShortDescription      This function is responsible to delete access token for current user
    * @param                 String $request
    * @return                Array of status and message
    */
    public function admin_logout(Request $request)
    {
        $request_data = array();
        $request_data['user_id'] = $this->security_lib_obj->decrypt(base64_decode($request['user_id']));
        $error = false;
        $validator = Validator::make($request_data, [
            'user_id' => 'required',
        ]);

        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        if($error == true){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Auth::messages.user_id'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user  = User::find($request_data['user_id']);

        if($user){
          $user->oauth_access_token()->delete();
          return $this->resultResponse(
              Config::get('restresponsecode.SUCCESS'), 
              [], 
              [],
              trans('Auth::messages.logged_out'),
              $this->http_codes['HTTP_OK']
          );
        }
        return $this->resultResponse(
          Config::get('restresponsecode.ERROR'), 
          [], 
          ["user" => [trans('Auth::messages.not_found')]],
          trans('Auth::messages.not_found'), 
          $this->http_codes['HTTP_NOT_FOUND']
        );

    }
    /**
     * @apiDesc This webservice enable Admin login
     * @apiParam string $mobile required  | Mobile number of admin  
     * @apiParam string $email  required         | email of admin
     * @apiParam password $password  required         | password of admin
     * @apiParam string $resource_type  | ('web', 'ios', 'android') From where data is coming 
     * @apiErr 422 | Validation errors
     * @apiErr 403 | Unauthorized access
     * @apiResp 200 | Whatever message is send from backend on sucess
     */ 
    public function admin_login(Request $request)
    {
        
        $request_data = $request->all();
        // Validate request
        $validate = $this->admin_login_validator($request_data);
        if($validate["error"]) {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('Auth::messages.validation_failed'), 
                    $this->http_codes['HTTP_OK']
                  ); 
        }
        
        //Check user login from Mobile or Email
        if(is_numeric($request_data['email'])){
            $input_column_name = 'mobile';
        } else {
            $input_column_name = 'email';
        }
        $input_column_value = $request_data['email'];
         
        $user_details = array();
        if(Auth::Attempt([$input_column_name => $input_column_value, 'password' => $request_data['password']])) {
            //Create Token for login user
            $user = Auth::user();
            $user_id = $user->user_id;   
            if($user->user_type == 'parent' || $user->user_type == 'driver' || $user->user_type == 'assistant'){
                return $this->echoResponse(
                        Config::get('restresponsecode.ERROR'), 
                        [], 
                        ["error" => [trans('Auth::messages.login_failed')]],
                        trans('Auth::messages.login_failed'), 
                        $this->http_codes['HTTP_OK']
                      ); 
            }
            $user_details['user'] = User::select('users.user_id', 'users.name', 'users.mobile','users.user_type', 'users.picture')
                                    ->where('users.user_id', '=', $user_id)
                                    ->where('is_deleted', '=', 0)
                                    ->first()
                                    ->toArray();
            //Encrypt user_id            
            $user_details['user']['user_id'] = base64_encode($this->security_lib_obj->encrypt($user->user_id));
            
            if(!empty($user_details['user']['picture'])) {
                $user_details['user']['picture'] = env('EDUCATION_APP_URL').'/api/media/user_picture/'.$user_details['user']['picture'];
            }
            $user_details['token'] = $user->createToken('Education')->accessToken;

            return $this->echoResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    $user_details, 
                    [],
                    trans('Auth::messages.loging_successfull'), 
                    $this->http_codes['HTTP_OK']
                  );
        }else {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    ["error" => [trans('Auth::messages.login_failed')]],
                    trans('Auth::messages.login_failed'), 
                    $this->http_codes['HTTP_OK']
                  );
        }     
    
    }

   /**
    * @DateOfCreation        05 June 2018
    * @ShortDescription      This function is responsible for validating for Admin login
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function admin_login_validator($data)
    {
        $error = false;
        $errors = [];
        $validation_data = [];
        // Check the login type is Email or Mobile
        if(is_numeric($data['email'])){
            $validation_data = [
                'email' => 'required|max:10',
            ];
        }else{
            $validation_data = [
                'email' => 'required|email|max:150',
            ];
        }

        $validation_data['password'] = 'required';
        
        $validator  = Validator::make(
            $data,
            $validation_data
        );

        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    
    /**
     * @apiDesc This webservice enable Assistant login
     * @apiParam string $pin required  | 4 digit number
     * @apiErr 422 | Validation errors
     * @apiErr 403 | Unauthorized access
     * @apiResp 200 | Whatever message is send from backend on sucess
     */ 
    public function assistant_login(Request $request)
    {
        $request_data = $this->security_lib_obj->decryptInput($request);

        // Validate request
        $validate = $this->assistant_login_validator($request_data);
        if($validate["error"]) {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('Auth::messages.validation_failed'), 
                    $this->http_codes['HTTP_OK']
                  );
        }

        $user_details = array();
        $select_data = [
                        "vehicle_id",
                       ];
        $where_data = [
                        "is_deleted" => 0,
                      ];
        $check_vehicle = DB::table("vehicles")
                             ->select($select_data)
                             ->where($where_data)
                             ->where(DB::raw("lower(TRIM(Replace(Replace(vehicles.registration_number,'-',''),' ','')))") , strtolower($request_data['registration_number']));
        if($check_vehicle->count() > 0){
            $check_vehicle = $check_vehicle->first();
            $vehicle_id = $check_vehicle->vehicle_id;
            $otp = $this->random_string(4, "number");
            
            $select_data = [
                            'vehicle_routes.vehicle_route_id',
                           ];
            $where_data = [
                            "vehicle_routes.vehicle_id" => $vehicle_id,
                            "vehicle_routes.is_deleted" => 0
                          ];
            $vehicle_details_by_route = DB::table("vehicle_routes")
                                         ->select($select_data)
                                         ->where($where_data)
                                         ->where(function ($query) {
                                                 $query->where('vehicle_routes.start_time', '<=', date("H:i:s", strtotime("+5 minute")))
                                                       ->where('vehicle_routes.end_time', '>=', date("H:i:s"));
                                         })
                                         ->join("employee_vehicles", "vehicle_routes.vehicle_id", "=", "employee_vehicles.vehicle_id");
            if($vehicle_details_by_route->count() > 0){
                // Send otp by email
                $this->send_email(array("otp"=>$otp, "registration_number" => $request_data['registration_number']));
                DB::table("vehicles")->where("vehicle_id", $vehicle_id)->update(["otp" => crypt($otp, Config::get('constants.CRYPT_KEY'))]);
                return $this->echoResponse(
                            Config::get('restresponsecode.SUCCESS'), 
                            [], 
                            [],
                            trans('Auth::messages.otp_send'), 
                            $this->http_codes['HTTP_OK']
                          );
            }
            else{
                $select_data = [
                                "trips.trip_id",
                                "trips.vehicle_route_id"
                               ];
                $where_data = [
                                "vehicle_routes.vehicle_id" => $vehicle_id,
                                "vehicle_routes.is_deleted" => 0,
                                "trips.is_deleted" => 0,
                                "trips.end_time" => NULL
                              ];
                $vehicle_details_by_trip = DB::table("trips")
                                             ->select($select_data)
                                             ->where($where_data)
                                             ->where("trips.start_time", "!=", NULL)
                                             ->join("vehicle_routes", 'trips.vehicle_route_id', "=", "vehicle_routes.vehicle_route_id")
                                             ->orderBy("trip_id", "DESC");
                if($vehicle_details_by_trip->count() > 0 ){
                    // Send otp to email
                    $this->send_email(array("otp"=>$otp, "registration_number" => $request_data['registration_number']));
                    DB::table("vehicles")->where("vehicle_id", $vehicle_id)->update(["otp" => crypt($otp, Config::get('constants.CRYPT_KEY'))]);
                    return $this->echoResponse(
                                Config::get('restresponsecode.SUCCESS'), 
                                [], 
                                [],
                                trans('Auth::messages.otp_send'), 
                                $this->http_codes['HTTP_OK']
                              );
                }else{
                    return $this->echoResponse(
                            Config::get('restresponsecode.SUCCESS'), 
                            [], 
                            [],
                            trans('Auth::messages.no_trips_available'), 
                            $this->http_codes['HTTP_OK']
                          );
                }
            }
        }
        else{
            return $this->echoResponse(
                            Config::get('restresponsecode.ERROR'), 
                            [], 
                            ["error" => [trans('Auth::messages.invalid_vehicle_number')]],
                            trans('Auth::messages.invalid_vehicle_number'), 
                            $this->http_codes['HTTP_OK']
                          );
        }
    }

    /**
    * @DateOfCreation        30 July 2018
    * @ShortDescription      This function is responsible for validating for Admin login
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function assistant_login_validator($data)
    {
        $error = false;
        $errors = [];
        $validation_data = [];
        // Check the login type is Email or Mobile
        $validation_data = [
            'registration_number' => 'required',
        ];
        
        $validator  = Validator::make(
            $data,
            $validation_data
        );

        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
     * @DateOfCreation          02 August 2018
     * @apiDesc                 This webservice will check login otp with associated vehicle
     * @apiParam                string $pin required  | 4 digit number
     * @apiErr                  422 | Validation errors
     * @apiErr                  403 | Unauthorized access
     * @apiResp                 200 | Whatever message is send from backend on sucess
     */ 
    public function verify_assistant_otp(Request $request)
    {
        $request_data = $this->security_lib_obj->decryptInput($request);
        // Validate request
        $validate = $this->verify_assistant_otp_validator($request_data);
        if($validate["error"]) {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('Auth::messages.validation_failed'), 
                    $this->http_codes['HTTP_OK']
                  );
        }
        $select_data = ['vehicles.vehicle_id', 'otp', 'employee_vehicles.user_assistant_id'];
        $where_data = [ 
                        'vehicles.is_deleted' => 0,
                        'employee_vehicles.is_deleted' => 0
                      ];
        $vehicle = DB::table("vehicles")
                        ->select($select_data)
                        ->where($where_data)
                        ->where(DB::raw("lower(TRIM(Replace(Replace(vehicles.registration_number,'-',''),' ','')))") , strtolower($request_data['registration_number']))
                        ->join("employee_vehicles", "vehicles.vehicle_id", "=", "employee_vehicles.vehicle_id")
                        ->first();
        if(!empty($vehicle) AND !empty($vehicle->otp)){
            if(hash_equals($vehicle->otp, crypt($request_data['otp'], Config::get('constants.CRYPT_KEY')))) {
                
                // Update otp field to NULL after verification success
                DB::table("vehicles")->where("vehicle_id", $vehicle->vehicle_id)->update(["otp" => NULL]);

                // Get user details
                $select_data = ['user_id', 'email', 'password', 'user_type', 'school_id'];
                $user = User::select($select_data)->where('user_id', $vehicle->user_assistant_id)->first();
                if(!empty($user)){
                    Auth::login($user);

                    // Create Token for login user
                    $user = Auth::user();
                    if(!empty($user)){
                        // Generate & store user short token for socket connection
                        $short_token = $this->create_short_token(50);
                        DB::table('users')->where("user_id", $user->user_id)->update(["short_token" => $short_token]);

                        // Check device id already exists.
                        $device_data = DB::table("devices")->where(["uuid" => $request_data['device_id'], "school_id" => $user->school_id, "is_deleted" => 0]);
                        // Device array.
                        $device_details = [
                                            "device_token" => $request_data['device_token'],
                                            "device_type" => $request_data['device_type'],
                                            "os_version" => $request_data['os_version'],
                                            "device_model" => $request_data['device_model'],
                                            "resource_type" => $request_data['resource_type'],
                                            "user_id" => $user->user_id,
                                            "user_type" => "assistant",
                                            "school_id" => $user->school_id,
                                            "created_by" => $user->user_id,
                                            "updated_by" => $user->user_id,
                                          ];
                        if($device_data->count() > 0){
                            DB::table("devices")->where("uuid", $request_data['device_id'])->update($device_details);
                        }
                        else{
                            $device_details["uuid"] = $request_data['device_id'];
                            $device = Device::create($device_details);
                            if(!empty($device)){
                                $d_name = "D".$device->device_id;
                                DB::table("devices")->where("device_id", $device->device_id)->update(['device_name' => $d_name, "device_reference" => $d_name]);
                            }
                        }

                        $user_details = array();
                        $where_data = [ 
                                        "user_id" => $user->user_id,
                                        "school_id" => $user->school_id,
                                        "is_deleted" => 0
                                      ];
                        $user_info = User::select('user_id', 'name','email', 'mobile','user_type', 'picture')
                                         ->where($where_data)
                                         ->first()
                                         ->toArray();
                        
                        $user_details['user_id'] = $user_info['user_id'];
                        $user_details['name'] = $user_info['name'];
                        if(!empty($user_info['picture'])) {
                            $user_details['picture'] = env('EDUCATION_APP_URL').'/api/media/user_picture/'.$user_info['picture'];
                        }
                        else{
                            $user_details['picture'] = env('EDUCATION_APP_URL').'/api/media/user_picture/default_user.png';
                        }
                        $user_details['mobile'] = $user_info['mobile'];
                        $user_details['user_type'] = $user_info['user_type'];
                        $user_details['email'] = $user_info['email'];
                        $user_details['token'] = $user->createToken('Education')->accessToken;
                        $user_details['short_token'] = $short_token;
                        $user_details['vehicle_route_id'] = 0;
                        $user_details['vehicle_id'] = $vehicle->vehicle_id;
                        $user_details['route_id'] = 0;
                        $user_details['school_id'] = $user->school_id;
                        $user_details['is_trip_started'] = 0;
                        $user_details['trip_id'] = 0;
                        $user_details['trip_type'] = "";
                        $user_details['route']['stoppage'] = array();
                        
                        // Fetch route details
                        $select_data = [
                                        'vehicle_routes.vehicle_route_id',
                                        'vehicle_routes.route_id',
                                        "routes.polyline"
                                       ];
                        $where_data = [
                                        "vehicle_routes.vehicle_id" => $vehicle->vehicle_id,
                                        "vehicle_routes.is_deleted" => 0,
                                        "vehicle_routes.school_id" => $user->school_id,
                                        "routes.is_deleted" => 0,
                                        "routes.school_id" => $user->school_id
                                      ];
                        $vehicle_details = DB::table("vehicle_routes")
                                             ->select($select_data)
                                             ->where($where_data)
                                             ->where(function ($query) {
                                                     $query->where('vehicle_routes.start_time', '<=', date("H:i:s", strtotime("+5 minute")))
                                                           ->where('vehicle_routes.end_time', '>=', date("H:i:s"));
                                             })
                                             ->join("routes", "vehicle_routes.route_id", "=", "routes.route_id");
                        if($vehicle_details->count() == 0){
                            $select_data = [
                                            "trips.vehicle_route_id",
                                            "trips.trip_id",
                                            "vehicle_routes.route_id",
                                            "routes.polyline"
                                           ];
                            $where_data = [
                                            "vehicle_routes.vehicle_id" => $vehicle->vehicle_id,
                                            "vehicle_routes.is_deleted" => 0,
                                            "vehicle_routes.school_id" => $user->school_id,
                                            "trips.is_deleted" => 0,
                                            "trips.school_id" => $user->school_id,
                                            "trips.end_time" => NULL,
                                            "routes.is_deleted" => 0,
                                            "routes.school_id" => $user->school_id
                                          ];
                            $vehicle_details = DB::table("trips")
                                                 ->select($select_data)
                                                 ->where($where_data)
                                                 ->where("trips.start_time", "!=", NULL)
                                                 ->join("vehicle_routes", 'trips.vehicle_route_id', "=", "vehicle_routes.vehicle_route_id")
                                                 ->join("routes", 'vehicle_routes.route_id', "=", "routes.route_id")
                                                 ->orderBy("trip_id", "DESC");
                            if($vehicle_details->count() > 0){
                                $user_details['is_trip_started'] = 1;
                            }
                        }
                        $vehicle_details = $vehicle_details->first();

                        // Attatch vehicle route id
                        $user_details['vehicle_route_id'] = $vehicle_details->vehicle_route_id;
                        if(!empty($vehicle_details->trip_id)){
                            $user_details['trip_id'] = $vehicle_details->trip_id;
                        }
                        $user_details['route_id'] = $vehicle_details->route_id;
                        
                        // Attatch polyline.
                        $polyline = json_decode($vehicle_details->polyline);
                        $user_details['polyline'] = array();
                        foreach($polyline as $ply){
                            $user_details['polyline'][] = array("points"=>$ply);
                        }
                        
                        // Get stoppage details.
                        $select_data = [
                                        'stoppages.stoppage_address',
                                        'stoppages.stoppage_latitude AS location_latitude',
                                        'stoppages.stoppage_longitude AS location_longitude',
                                        'stoppages.stoppage_id',
                                        'vehicle_route_schedules.schedule_time',
                                        'vehicle_route_schedules.route_id',
                                       ];
                        $where_data = [
                                        "vehicle_route_schedules.vehicle_route_id" => $vehicle_details->vehicle_route_id,
                                        "vehicle_route_schedules.is_deleted" => 0,
                                        "vehicle_route_schedules.school_id" => $user->school_id,
                                        "stoppages.is_deleted" => 0,
                                        "stoppages.school_id" => $user->school_id
                                      ];
                        $stoppage_list = DB::table('vehicle_route_schedules')
                                             ->select($select_data)
                                             ->where($where_data)
                                             ->join("stoppages", "vehicle_route_schedules.stoppage_id", "=", "stoppages.stoppage_id")
                                             ->get()
                                             ->toArray();
                        
                        // Fetch student list
                        foreach($stoppage_list as $stops){
                            $student_checkin_count = 0;
                            $student_checkin_ids = array();
                            if($user_details['is_trip_started']){
                                $checkin_students = DB::table('student_checkins')
                                                        ->select('student_checkins.student_id')
                                                        ->where([ 
                                                                  "student_checkins.checkin_stoppage_id" => $stops->stoppage_id,
                                                                  "student_vehicle_routes.vehicle_route_id" => $user_details['vehicle_route_id'],
                                                               ])
                                                        ->where(DB::raw("student_checkins.created_at::timestamp::date"), date("Y-m-d"))
                                                        ->join('student_vehicle_routes', function($join){
                                                              $join->on('student_vehicle_routes.student_id', '=', 'student_checkins.student_id')
                                                                   ->on('student_vehicle_routes.vehicle_id', '=', 'student_checkins.vehicle_id')
                                                                   ->on('student_vehicle_routes.route_id', '=', 'student_checkins.route_id')
                                                                   ->on('student_vehicle_routes.stoppage_pickup', '=', 'student_checkins.checkin_stoppage_id')
                                                                   ->on('student_vehicle_routes.school_id', '=', 'student_checkins.school_id');
                                                        });
                                $student_checkin_count = $checkin_students->count();
                                if($student_checkin_count > 0){
                                    $student_checkin_ids = array();
                                    foreach($checkin_students->get() as $st){
                                        $student_checkin_ids[] = $st->student_id;
                                    }
                                }
                            }
                            $temp_array = array(
                                                "stoppage_id" => $stops->stoppage_id,
                                                "stoppage_address" => $stops->stoppage_address,
                                                "location_latitude" => floatval($stops->location_latitude),
                                                "location_longitude" => floatval($stops->location_longitude),
                                                "schedule_time" => date("H:i A", strtotime($stops->schedule_time)),
                                               );

                            // Get student list for an stoppage
                            $select_data = [
                                            "students.student_id",
                                            "students.name AS student_name",
                                            "students.class",
                                            "students.student_picture",
                                            "student_vehicle_routes.pickup_time as checkin_time",
                                            "student_vehicle_routes.route_type",
                                            "beacons.beacon_id as beacon_id",
                                            "beacons.major",
                                            "beacons.miner",
                                            "beacons.uuid",
                                            ];
                            $where_data = [
                                            "student_vehicle_routes.vehicle_id" => $vehicle->vehicle_id,
                                            "student_vehicle_routes.route_id" => $stops->route_id,
                                            "student_vehicle_routes.stoppage_pickup" => $stops->stoppage_id,
                                            "student_vehicle_routes.school_id" => $user->school_id,
                                            "student_vehicle_routes.is_deleted" => 0,
                                            "students.is_deleted" => 0
                                          ];
                            // Get student list bording from an stoppage.
                            $students = DB::table('student_vehicle_routes')
                                            ->select($select_data)
                                            ->where($where_data)
                                            ->join('students', 'student_vehicle_routes.student_id', "=", "students.student_id")
                                            ->leftJoin('beacons', 'students.beacon_id', "=", "beacons.beacon_id");

                            // Get total student count from an stoppage.
                            $temp_array['student_count'] = $students->count();
                            $temp_array['student_checkin_count'] = $student_checkin_count;
                            $temp_array['students'] = array();
                            
                            // Get student list from db query
                            $students = $students->get();
                            foreach($students as $stud){

                                // Add path to student picture
                                if(!empty($stud->student_picture)) {
                                    $stud_picture = env('EDUCATION_APP_URL').'/api/media/user_picture/'.$stud->student_picture;
                                }
                                else{
                                    $stud_picture = env('EDUCATION_APP_URL').'/api/media/user_picture/default_user.png';
                                }

                                // Initialize beacon array
                                if(!empty($stud->beacon_id)){
                                    $beacon = array(
                                                    "beacon_id" => $stud->beacon_id,
                                                    "uuid" => $stud->uuid,
                                                    "major" => $stud->major,
                                                    "miner" => $stud->miner
                                                );
                                }
                                else{
                                    $beacon = new ArrayObject();
                                }
                                $is_checkin = 0;
                                if($user_details['is_trip_started'] AND !empty($student_checkin_ids) AND in_array($stud->student_id, $student_checkin_ids)){
                                    $is_checkin = 1;
                                }
                                $temp_array['students'][] = array(  
                                                                "student_id" => $stud->student_id,
                                                                "student_name" => $stud->student_name,
                                                                "class" => $stud->class,
                                                                "photo" => $stud_picture,
                                                                "checkin_time" => date("H:i A", strtotime($stud->checkin_time)),
                                                                "is_checkin" => $is_checkin,
                                                                "beacon" => $beacon
                                                             );
                                $user_details['trip_type'] = $stud->route_type;
                            }
                            $user_details['route']['stoppage'][] = $temp_array;
                        }
                        

                        // Get sos list
                        $sos = DB::table('delay_reasons')
                                  ->select('description as delay_reason')
                                  ->where(["school_id" => $user->school_id, "is_deleted" => 0])
                                  ->get()
                                  ->toArray();
                        $user_details['sos'] = $sos;

                        // Get check in reason list
                        $checin_reasons = DB::table("check_in_reasons")
                                              ->select('check_in_reason_id', 'reason_type', 'reason')
                                              ->where(["school_id" => $user->school_id, "is_deleted" => 0])
                                              ->get()
                                              ->toArray();
                        $check_reasons_array = array('in' => array(), 'out' => array());
                        if(!empty($checin_reasons)){
                            foreach($checin_reasons as $reason){
                                if($reason->reason_type == 'checkin'){
                                    $check_reasons_array['in'][] = array(
                                                                         "reason_id" => $reason->check_in_reason_id,
                                                                         "reason" => $reason->reason
                                                                        );
                                }
                                else{
                                    $check_reasons_array['out'][] = array(
                                                                         "reason_id" => $reason->check_in_reason_id,
                                                                         "reason" => $reason->reason
                                                                        );
                                }
                            }
                        }
                        $user_details['check'] = $check_reasons_array;

                        return $this->echoResponse(
                                    Config::get('restresponsecode.SUCCESS'), 
                                    [$user_details], 
                                    [],
                                    trans('Auth::messages.login_successfull'), 
                                    $this->http_codes['HTTP_OK']
                                  );
                    }else {
                        return $this->echoResponse(
                                Config::get('restresponsecode.ERROR'), 
                                [], 
                                ["error" => [trans('Auth::messages.login_failed')]],
                                trans('Auth::messages.login_failed'), 
                                $this->http_codes['HTTP_OK']
                              );
                    }
                }else {
                    return $this->echoResponse(
                            Config::get('restresponsecode.ERROR'), 
                            [], 
                            ["error" => [trans('Auth::messages.login_failed')]],
                            trans('Auth::messages.login_failed'), 
                            $this->http_codes['HTTP_OK']
                          );
                }
            }
            else {
                return $this->echoResponse(
                        Config::get('restresponsecode.ERROR'), 
                        [], 
                        ["error" => [trans('Auth::messages.invalid_otp')]],
                        trans('Auth::messages.invalid_otp'), 
                        $this->http_codes['HTTP_OK']
                      );
            }
        }
        else {
            return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    ["error" => [trans('Auth::messages.login_failed')]],
                    trans('Auth::messages.login_failed'), 
                    $this->http_codes['HTTP_OK']
                  );
        }
    }

    /**
    * @DateOfCreation        02 August 2018
    * @ShortDescription      This function is responsible for validating login otp request
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function verify_assistant_otp_validator($data)
    {
        $error = false;
        $errors = [];
        $validation_data = [];

        // Check the login type is Email or Mobile
        $validation_data = [
            'registration_number' => 'required',
            'otp' => 'required'
        ];
        $validator  = Validator::make(
            $data,
            $validation_data
        );
        if($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
    * @DateOfCreation        02 August 2018
    * @ShortDescription      This function is responsible to delete access token for current assistant user
    * @param                 String $request
    * @return                Array of status and message
    */
    public function assistant_logout(Request $request)
    {
        $request_data =$this->security_lib_obj->decryptInput($request);
        $error = false;
        $validator = Validator::make($request_data, [
            'user_id' => 'required'            
        ]);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        if($error == true){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Auth::messages.user_id'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user  = User::find($request_data['user_id']);

        if($user){

            // Update user short taken
            $user->short_token = NULL;
            $user->save();

            // Update device token
            $whereData   =  [
                                'user_id'=> $request_data['user_id'],
                                'device_token'=> $request_data['device_token']
                            ];
            $check_user_token = Device::where($whereData)->update(["device_token"=>NULL]);
            $user->oauth_access_token()->delete();
            return $this->resultResponse(
                            Config::get('restresponsecode.SUCCESS'), 
                            [], 
                            [],
                            trans('Auth::messages.logged_out'),
                            $this->http_codes['HTTP_OK']
                        );
        }
        return $this->resultResponse(
          Config::get('restresponsecode.ERROR'), 
          [], 
          ["user" => [trans('Auth::messages.not_found')]],
          trans('Auth::messages.not_found'), 
          $this->http_codes['HTTP_NOT_FOUND']
        );
    }

    /**
    * @DateOfCreation        01 August 2018
    * @ShortDescription      This function is responsible for generating a unique short token for user.
    * @param                 Integer $len 
    * @return                String
    */
    function create_short_token($len)
    {
        $short_token = $this->random_string($len);
        $check_token = DB::table("users")->where("short_token", $short_token)->count();
        if($check_token > 0){
            $this->create_short_token($len);
        }
        else{
            return $short_token;
        }
    }

    /**
    * @DateOfCreation        01 August 2018
    * @ShortDescription      This function is used to generate random alphanumeric string.
    * @param                 Integor $len 
    * @return                String
    */
    function random_string($len, $type = 'alphanumeric')
    {
        if($type == "number"){
            $chars = "123456789";
        }
        if($type == "alphanumeric"){
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        }
        $str = '';
        $max = strlen($chars) - 1;
        for ($i=0; $i < $len; $i++){
            $str .= $chars[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      This function is responsible for sending email
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function send_email($data)
    {
        $sent = Mail::send('emails.assistant_otp', ['otp' => $data['otp'], 'registration_number' => $data['registration_number']], function($message){
                  $message->from(Config::get('constants.MAIL_FROM'), 'Education');
                  $message->to(config::get('constants.OTP_EMAILS'));
                  $message->subject(trans('Auth::messages.login_otp'));
                });
        return $sent;
    }
}