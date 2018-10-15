<?php

namespace App\Modules\Parents\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Config;
use Carbon\Carbon;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Models\Student;
use App\Models\StudentCheckInOut;


/**
 * EventController Class
 *
 * @package                Education
 * @subpackage             EventController
 * @category               Controller
 * @DateOfCreation         01 June 2018
 * @ShortDescription       This controller perform passenger calendar request events  functionality for api
 */

class EventController extends Controller
{
    use RestApi;

   // @var Array $http_codes
    // This protected member contains Http Status Codes
    protected $http_codes = [];

   /**
    * Create a new controller instance.
    * @return void
    */
    public function __construct()
    {
        // Json array of http codes.
        $this->http_codes = $this->http_status_codes();

        // Init security library object
        $this->security_lib_obj = new SecurityLib();  
    }

   /**
    * @DateOfCreation        19 July 2018
    * @ShortDescription      Check In and Check Out Students
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function check_in_out(Request $request)
    {
        $user_id = Auth::id();
        $request_data =$this->security_lib_obj->decryptInput($request);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $today = date('Y-m-d');
        // Validate request
        $validate = $this->check_in_validator($request_data);
        if($validate["error"])
        {
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $validate['errors'],
                    trans('Parents::event.error_message'), 
                    $this->http_codes['HTTP_OK']
                  ); 
        }        
      
        $checkin_type = strtolower($request_data['checkin_type']);
        $source_type = strtolower($request_data['source_type']);
        $resource_type = $request_data['resource_type'];
        $current_time = Carbon::now();        
        $latitude = $request_data['latitude'];
        $longitude = $request_data['longitude'];        
        
        /**
        * Todo        23 May 2018
        * @ShortDescription      Find and update address from lat and long in PHP cli
        * We will remove the hardcode location id
        */ 
        $location_id = 3;
        
        //Get school id 
        $school_id = Student::find($request_data['student_id'])->school_id;
        
        $student_check_in_out = new StudentCheckInOut;
        //Check checkin user
        $exist_check = $student_check_in_out->select('student_checkin_id')
                        ->where([
                            ['student_id', '=', $request_data['student_id']], 
                            ['vehicle_id', '=', $request_data['vehicle_id']], 
                            ['route_id', '=', $request_data['route_id']], 
                            ['checkout_time', '=', null]
                         ])
                        ->whereBetween('checkin_time', [$today.' 00:00:00', $today.' 23:59:59']);  

        $check_in_out = 'check_in_error';
        if($exist_check->count() == 0 && $checkin_type == 'pickup' ){
            //Check In Save
            $student_check_in_out->student_id = $request_data['student_id'];
            $student_check_in_out->vehicle_id = $request_data['vehicle_id'];
            $student_check_in_out->route_id = $request_data['route_id'];
            $student_check_in_out->school_id = $school_id;
            $student_check_in_out->user_id = $user_id;
            $student_check_in_out->checkin_time = $current_time;
            $student_check_in_out->checkin_latitude = $latitude;
            $student_check_in_out->checkin_longitude = $longitude;
            $student_check_in_out->checkin_location_id = $location_id;
            $student_check_in_out->checkin_source = $source_type;
            $student_check_in_out->created_by = $user_id;
            $student_check_in_out->updated_by = $user_id;
            $student_check_in_out->resource_type = $resource_type;
            $student_check_in_out->user_agent = $user_agent;
            $student_check_in_out->ip_address = $request->ip();
            $student_check_in_out->save();
            $check_in_out = 'check_in';
        }else if($exist_check->count() > 0 && $checkin_type == 'drop'){
           //Check Out Update
            $exist_check = $exist_check->first();
            $exist_check->student_checkin_id;
            $exist_check->checkout_time = $current_time;
            $exist_check->checkout_latitude = $latitude;
            $exist_check->checkout_longitude = $longitude;
            $exist_check->checkout_location_id = $location_id;
            $exist_check->checkout_source = $source_type;
            $exist_check->updated_by = $user_id;
            $exist_check->resource_type = $resource_type;
            $exist_check->user_agent = $user_agent;
            $exist_check->ip_address = $request->ip();
            $exist_check->save();
            $check_in_out = 'check_out';
        }else if($checkin_type == 'drop'){ 
            $check_in_out = 'check_out_error';
        }

        return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    [], 
                    [],
                    trans('Parents::event.'.$check_in_out), 
                    $this->http_codes['HTTP_OK']
                  );
    }

    /**
    * @DateOfCreation        21 May 2018
    * @ShortDescription      This function is responsible for validating for CheckInOut
    * @param                 Array $data This contains full input data 
    * @return                Array
    */ 
    protected function check_in_validator(array $data)
    {
        
        $error = false;
        $errors = [];
        
        $validator = Validator::make($data, [
            'student_id' => 'required',
            'vehicle_id' => 'required',
            'route_id' => 'required',
            'checkin_type' => 'required',
            'source_type' => 'required',
        ]);        
        
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
    * @DateOfCreation        18 July 2018
    * @ShortDescription      This is used for news list.
    * @return                Array of status and message
    */ 
    public function news()
    {
        $user_id = Auth::id();
        
        $news_details = News::select('schools.school_id','schools.name as school_name','students.student_id','students.name as student_name','news_id','title', 'image', 'description', 'news_date')
                            ->join('schools', 'schools.school_id','=','news.school_id')
                            ->join('students', 'students.student_id','=','schools.school_id')
                            ->join('student_parents', 'student_parents.student_id','=','students.student_id')
                            ->join('users', 'users.user_id','=','student_parents.user_id')
                            ->where('student_parents.user_id', '=', $user_id)
                            ->get()->toArray();

        if ($news_details) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [$news_details], 
                [],
                trans('Parents::event.news_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::event.news_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }
}