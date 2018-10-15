<?php

namespace App\Modules\Assistant\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\News;
use App\Models\Notification;
use App\Models\Student;
use App\Models\Trip;
use App\Models\StudentCheckIn;
use App\Libraries\FileLib;
use App\Libraries\ImageLib;
use App\Libraries\SecurityLib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Traits\RestApi;
use Carbon\Carbon;
use Validator;
use Config;
use File;
use Response;
use DB;
use Edujugon\PushNotification\PushNotification;

class AssistantController extends Controller
{

     use RestApi;

   /** @var                Array $http_codes
    *  @ShortDescription   This protected member contains http status Codes
    */
    protected $http_codes = [];

   /**
    * Create a new controller instance.
    * @return void
    */
    public function __construct()
    {
        // Json array of http codes.
        $this->http_codes = $this->http_status_codes();

        // Init File Library object
        $this->file_lib = new FileLib();

        // Init Image Library object
        $this->image_lib = new ImageLib();

        // Init security library object
        $this->security_lib_obj = new SecurityLib();  
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkin(Request $request)
    {       
        
        $user_id = Auth::id();
        $request_data = $this->security_lib_obj->decryptInput($request);       

        $validator = Validator::make($request_data, [            
            'student_id' => 'required',
            'checkin_type' =>[
        'required',
        Rule::in(['bus-checkin-approval','bus-checkout-to-school','bus-checkin-from-school','bus-checkout-approval']),
        ],            
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
                trans('Parents::parent.parent_validation'),
                $this->http_codes['HTTP_OK']
            ); 
        }

        //code to send notification 
        
        $student_id=$request_data['student_id'];
        $checkin_type=$request_data['checkin_type'];

        $student_details = Student::select( 'students.name')
                            ->where('students.student_id', '=', $student_id)
                            ->where('students.is_deleted', '=', 0)
                            ->get()
                            ->toArray();      

        if(empty($student_details)){

            $errors["student_id"]=  trans('Assistant::assistant.student_id_not_found');
            
             return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
               trans('Assistant::assistant.student_id_not_found'),
                $this->http_codes['HTTP_OK']
            );
        }else{
            $student_name=$student_details[0]['name'];
        }       

        $body="";
        $title="";

        switch ($checkin_type) {
            case 'bus-checkin-approval':
                //bus-checkin-from-home
                $body="Your child ".$student_name." is check-in to Bus MP 09 NV 2007 at 8.00 AM from Vijay nagar, plesae approve it.";
                $title="Bus checkin from stoppage";
                $notification_type="bus-checkin-approval";
                break;
            case 'bus-checkout-to-school':
               $body="Your child ".$student_name." is check-out from Bus MP 09 NV 2007 to IDPS at 9.00 AM.";
               $title="Checkout from bus to school";
               $notification_type="bus-checkout-to-school";
                break;
            case 'bus-checkin-from-school':
                $body="Your child ".$student_name." is check-in to Bus MP 09 NV 2007 at 2.10 PM from IDPS";
                $title="Checkin from school to bus";
                $notification_type="bus-checkin-from-school";
                break;
            case 'bus-checkout-approval':
                //bus-checkout-to-home
                $body="Your child ".$student_name." is check-out from Bus MP 09 NV 2007 at Vijay nagar by 3.00 PM, plesae approve it.";
                $title="Checkout from bus to stoppage";
                $notification_type="bus-checkout-approval";
                break;
            
            default:
                # checking type validation already done above
                break;
        }

        //find devide token to send push notification
        $tokens = Student::select('devices.device_token', 'devices.device_type','student_parents.user_id')
                            ->from('devices')
                            ->join('student_parents', 'student_parents.user_id','=','devices.user_id')
                            ->where('student_parents.student_id', '=', $student_id)
                            ->where('student_parents.is_deleted', '=', 0)
                            ->where('devices.is_deleted', '=', 0)
                            ->get()
                            ->toArray();
        

        if (empty($tokens)) {

            $errors["device_token"]=  trans('Assistant::assistant.no_device_to_sent_notification');
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
               trans('Assistant::assistant.no_device_to_sent_notification'),
                $this->http_codes['HTTP_OK']
            );

        }

        //save data to student_checkin table
        $student_checkin_model = new StudentCheckIn; 
        $data_array=array();
        $data_array['student_id'] = $student_id;
        $data_array['resource_type'] = 'ios';
        $data_array['user_id'] = $user_id;
       /* $data_array['vehicle_id'] = $vehicle_id;
        $data_array['route_id'] = $route_id;
        $data_array['school_id'] = $school_id;
        $data_array['checkin_location_id'] = $checkin_location_id;
        $data_array['checkout_location_id'] = $checkout_location_id;
        $data_array['checkin_latitude'] = $checkin_latitude;
        $data_array['checkin_longitude'] = $checkin_longitude;
        $data_array['checkout_latitude'] = $checkout_latitude;
        $data_array['checkout_longitude'] = $checkout_longitude;
        
        $data_array['checkout_source'] = $checkout_source;
        $data_array['route_type'] = $route_type;
        $data_array['checkin_time'] = $checkin_time;
        $data_array['checkout_time'] = $checkout_time;*/
        $data_array['checkin_source'] = 'assistant';
        $data_array['checkout_source'] = 'assistant';
        $data_array['user_agent']  = $request->server('HTTP_USER_AGENT');
        $data_array['ip_address']  = $request->ip();
        //$data_array['created_at']  = date('Y-m-d H:i:s');
        $data_array['created_by']  = $user_id;
        $data_array['updated_by']  = $user_id;                       

        //Insert into 
        $student_checkin_id=$student_checkin_model->create($data_array)->student_checkin_id;        

        $android_tokens=array();
        $ios_tokens=array();
        $parents=array();
        foreach ($tokens as $value) {
            
            $parents[]=$value['user_id'];

            if( strtolower($value['device_type'])=='android')
                $android_tokens[]=$value['device_token'];
            else
                $ios_tokens[]=$value['device_token'];
        }          
     
        //iOS notification code start        
        
        if(!empty($ios_tokens)){
            $push_ios = new PushNotification('apn');

            $push_ios->setMessage([
                'aps' => [
                    'alert' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'sound' => 'default',
                    'badge' => 1

                ],
                'extraPayLoad' => [
                    'student_checkin_id' =>  $student_checkin_id,
                    'checkin_type' =>  $notification_type,
                ]
            ])
            ->setDevicesToken($ios_tokens);        

            $push_data_ios = $push_ios->send()->getFeedback();
        }

        //iOS notification code end

        //Android notification code start
        if(!empty($android_tokens)){
            $push_android = new PushNotification('fcm');
            $push_android->setMessage([
            'notification' => [
                    'title'=>$title,
                    'body'=>$body,
                    'sound' => 'default'
                    ],
            'data' => [
                    'student_checkin_id' =>  $student_checkin_id,
                    'checkin_type' =>  $notification_type,
                    ]
            ])
                ->setApiKey(Config::get('pushnotification.fcm.apiKey'))  
                ->setDevicesToken($android_tokens)
                ->setConfig(['dry_run' => false]);
            $push_data_android = $push_android->send()->getFeedback();        
        }
        // Android notification code end

         // save data to notification table for each parent
        
        $notification_model = new Notification; 
        
        //only one row for each parent
        $parents=array_unique($parents);
        
        foreach ($parents as $parent_id) {

            $data_array=array();
            $data_array['user_id'] = $parent_id;
            $data_array['resource_type'] = 'ios';
            $data_array['user_agent']  = $request->server('HTTP_USER_AGENT');
            $data_array['ip_address']  = $request->ip();
            $data_array['created_at']  = date('Y-m-d H:i:s');
            $data_array['created_by']  = $user_id;
            $data_array['updated_by']  = $user_id;
            $data_array['notification_type'] = $notification_type;
            $data_array['description'] = $body;
            $data_array['payload'] = $student_checkin_id;     
            $data_array['title'] = $title;                                        

            //Insert into 
            $result = $notification_model->insert($data_array);
         
        }

        //print to check any notification failure
        //print_r($push_data_ios);

        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [], 
            [],
            trans('Parents::parent.notification_detail_successfull'), 
            $this->http_codes['HTTP_OK']
        );
        
    }

    /**
    * @DateOfCreation        02 August 2018
    * @ShortDescription      This function is used to fetch assistant user notification.
    * @return                Array of status and message
    */
    public function notifications(Request $request)
    {
        $user_id = Auth::id();
        $selectData  =  [ 'notification_id', 'description', 'title', 'created_at as notification_date'];
        $whereData   =  [
                            'user_id'=> $user_id,
                            'is_deleted'=>  0
                        ];
        $notification_list = Notification::select($selectData)
                                         ->where($whereData)
                                         ->orderBy('notification_id', 'DESC')
                                         ->offset(0)
                                         ->limit(20)
                                         ->get()
                                         ->toArray();
        foreach($notification_list as &$not){
            $not['notification_date'] = date("d/m/Y h.i A", strtotime($not['notification_date']));
            $not['title'] = empty($not['title']) ? '' : $not['title'];
        }
        if (!empty($notification_list) && isset($notification_list)) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $notification_list, 
                [],
                trans('Assistant::assistant.notification_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Assistant::assistant.notification_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }

    /**
    * @DateOfCreation        03 August 2018
    * @ShortDescription      This function is used to send notification for bus delay to student's parents.
    * @return                Array of status and message
    */
    public function sos(Request $request){
        $user = Auth::user();
        $request_data = $this->security_lib_obj->decryptInput($request);

        // Apply validation rules to request data
        $validator = Validator::make($request_data, [            
            'vehicle_route_id' => 'required',
            'delay_reason' => 'required',
            'delay_time' => 'required',            
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
                trans('Parents::parent.parent_validation'),
                $this->http_codes['HTTP_OK']
            ); 
        }
        $select_data = ["students.student_id", "student_parents.user_id", "devices.device_type", "devices.device_token"];
        $where_data = [
                        'student_parents.school_id' => $user->school_id,
                        'student_parents.is_deleted' => 0,
                        'students.school_id' => $user->school_id,
                        'students.is_deleted' => 0,
                        'student_vehicle_routes.school_id' => $user->school_id,
                        'student_vehicle_routes.is_deleted' => 0,
                        'vehicle_routes.school_id' => $user->school_id,
                        'vehicle_routes.is_deleted' => 0,
                        'vehicle_routes.vehicle_route_id' => $request_data['vehicle_route_id'],
                    ];
        $parent_list = DB::table("student_parents")
                            ->select($select_data)
                            ->join("devices", "student_parents.user_id", "=", "devices.user_id")
                            ->join("students", "student_parents.student_id", "=", "students.student_id")
                            ->join('student_vehicle_routes', 'students.student_id', "=", "student_vehicle_routes.student_id")
                            ->join('vehicle_routes', function($join){
                                  $join->on('student_vehicle_routes.route_id', '=', 'vehicle_routes.route_id')
                                  ->on('student_vehicle_routes.vehicle_id', '=', 'vehicle_routes.vehicle_id');
                              })
                            ->where($where_data);
        // Send notifications to parents
        if($parent_list->count() > 0){
            $parent_array = $parent_list->get()->toArray();
            $android_tokens = array();
            $ios_tokens = array();
            $parents = array();
            $title="Bus delay";
            $body="Bus will be delayed by ".$request_data['delay_time']." min due to ".$request_data['delay_reason'].".";
            foreach($parent_array as $value){
                $parents[] = $value->user_id;
                if(strtolower($value->device_type) == 'android'){
                    $android_tokens[] = $value->device_token;
                }
                else{
                    $ios_tokens[] = $value->device_token;
                }
            }
            // IOS notification code start
                if(!empty($ios_tokens)){
                    $push_ios = new PushNotification('apn');

                    $push_ios->setMessage([
                        'aps' => [
                            'alert' => [
                                'title' => $title,
                                'body' => $body
                            ],
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ])
                    ->setDevicesToken($ios_tokens);        

                    $push_data_ios = $push_ios->send()->getFeedback();
                }
            // IOS notification code end

            // Android notification code start
                if(!empty($android_tokens)){
                    $push_android = new PushNotification('fcm');
                    $push_android->setMessage([
                    'notification' => [
                            'title'=>$title,
                            'body'=>$body,
                            'sound' => 'default'
                            ]
                    ])
                        ->setApiKey(Config::get('pushnotification.fcm.apiKey'))  
                        ->setDevicesToken($android_tokens)
                        ->setConfig(['dry_run' => false]);
                    $push_data_android = $push_android->send()->getFeedback();        
                }
            // Android notification code end

            // Save data to notification table for each parent
            $notification_model = new Notification;
            foreach ($parents as $parent_id) {
                $data_array=array();
                $data_array['user_id'] = $parent_id;
                $data_array['resource_type'] = 'ios';
                $data_array['user_agent']  = $request->server('HTTP_USER_AGENT');
                $data_array['ip_address']  = $request->ip();
                $data_array['created_at']  = date('Y-m-d H:i:s');
                $data_array['created_by']  = $user->user_id;
                $data_array['updated_by']  = $user->user_id;
                $data_array['notification_type'] = NULL;
                $data_array['description'] = $body;
                $data_array['payload'] = NULL;
                $data_array['title'] = $title;                                        

                //Insert into 
                $result = $notification_model->insert($data_array);
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [], 
            [],
            trans('Parents::parent.notification_detail_successfull'), 
            $this->http_codes['HTTP_OK']
        );
    }

    /**
    * @DateOfCreation        03 August 2018
    * @ShortDescription      This function is used to return student piture in base64 format.
    * @return                Array of status and message
    */
    public function student_image(Request $request){
        $user = Auth::user();
        $request_data = $this->security_lib_obj->decryptInput($request);

        if(!empty($request_data['student_id'])){
            $student = DB::table("students")
                                ->select("student_picture")
                                ->where([
                                    "student_id" => $request_data['student_id'],
                                    "school_id" => $user->school_id,
                                    "is_deleted" => 0
                                ])
                                ->first();
            $image_path = storage_path('student_picture/');
            if(!empty($student) AND !empty($student->student_picture)) {
                $path = $image_path.$student->student_picture;
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                echo $base64;
            }
            else{
                $path = $image_path.'default_user.png';
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                echo $base64;
            }
        }
        else{
            $path = $image_path.'default_user.png';
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            echo $base64;
        }
    }

    /**
    * @DateOfCreation        06 August 2018
    * @ShortDescription      This function is responsible to update start
    *                        and end time for a trip
    * @param                 String $request
    * @return                Array of status and message
    */
    public function trip_status(Request $request)
    {
        $user = Auth::user();
        $school_id = $user->school_id;
        $user_id = $user->user_id;
        $request_data = $this->security_lib_obj->decryptInput($request);
        $error = false;
        $val_rule = array('type' => 'required',
                          'vehicle_route_id' => 'required');
        if($request_data['type'] == "end"){
            $val_rule['trip_id'] = "required";
        }
        $validator = Validator::make($request_data, $val_rule);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        if($error == true){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Assistant::assistant.validation_failed'), 
                $this->http_codes['HTTP_OK']
            );
        }
        $trip_details['user_agent'] = $request->server('HTTP_USER_AGENT');
        $trip_details['ip_address'] = $request->ip();

        if($request_data['type'] == "start"){
            $trip_details['school_id'] = $school_id;
            $trip_details['vehicle_route_id'] = $request_data['vehicle_route_id'];
            $trip_details['created_by'] = $user_id;
            $trip_details['updated_by'] = $user_id;
            $trip_details['resource_type'] = "Android";
            $trip_details['start_time'] = date("H:i:s");
            $route_details = DB::table('vehicle_routes')
                                ->select("employee_vehicles.user_driver_id", "employee_vehicles.user_assistant_id")
                                ->where([
                                            "vehicle_routes.vehicle_route_id" => $request_data['vehicle_route_id'],
                                            "employee_vehicles.school_id" => $school_id,
                                            "employee_vehicles.is_deleted" => 0
                                        ]
                                    )
                                ->join("employee_vehicles", "vehicle_routes.vehicle_id", "=", "employee_vehicles.vehicle_id")
                                ->first();
            $trip_details['assistant_id'] = $route_details->user_assistant_id;
            $trip_details['driver_id'] = $route_details->user_driver_id;
            $trip = Trip::create($trip_details);
            return $this->resultResponse(
                        Config::get('restresponsecode.SUCCESS'),
                        [array("trip_id" => $trip->trip_id )],
                        [],
                        trans('Assistant::assistant.trip_status_success'),
                        $this->http_codes['HTTP_OK']
                    );
        }
        else if($request_data['type'] == "end"){
            $trip_details['end_time'] = date("H:i:s");
            $trip_details['updated_by'] = $user_id;
            Trip::where("trip_id", $request_data['trip_id'])->update($trip_details);
        }
        return $this->resultResponse(
                        Config::get('restresponsecode.SUCCESS'),
                        [],
                        [],
                        trans('Assistant::assistant.trip_status_success'),
                        $this->http_codes['HTTP_OK']
                    );
    }

    /**
    * @DateOfCreation        10 August 2018
    * @ShortDescription      This function is responsible to send apn notification
    * @param                 String $request
    * @return                Array of status code
    */
    public function send_apn_notification(Request $request)
    {   
    	// if action is delay 
		if($request->method_type == 'delay'){
    		$push_ios = new PushNotification('apn');
            $push_ios->setMessage([
                'aps' => [
                    'alert' => [
                        'title' => $request->msg_title,
                        'body' => $request->msg_body
                    ],
                    'sound' => $request->msg_sound,
                    'badge' => 1
                ],
            ])
            ->setDevicesToken($request->student_ios_tokens);
            $push_data_ios = $push_ios->send()->getFeedback();
    	}

    	// if action is checkin
    	if($request->method_type == 'checkin'){
    		$push_ios = new PushNotification('apn');
            $push_ios->setMessage([
                'aps' => [
                    'alert' => [
                        'title' => $request->msg_title,
                        'body' => $request->msg_body
                    ],
                    'sound' => $request->msg_sound,
                    'badge' => 1
                ],
                'extraPayLoad' => [
                    'student_checkin_id' =>  $request->student_checkin_id,
                    'checkin_type' =>  $request->notification_type,
                ]
            ])
            ->setDevicesToken($request->student_ios_tokens);
            $push_data_ios = $push_ios->send()->getFeedback();
    	}

    	$resp = array('status_code' => 'SUCCESS');
		return response()->json($resp);    	
    }
    
}
