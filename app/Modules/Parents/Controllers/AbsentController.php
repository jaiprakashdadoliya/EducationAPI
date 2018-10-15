<?php

namespace App\Modules\Parents\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\News;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentAbsent;
use App\Models\StudentCheckIn;
use App\Models\StudentVehicleRoute;
use App\Models\Stoppage;
use App\Libraries\FileLib;
use App\Libraries\ImageLib;
use App\Libraries\SecurityLib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\RestApi;
use Carbon\Carbon;
use Validator;
use Config;
use File;
use Response;
use DB;

class AbsentController extends Controller
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
    * @DateOfCreation        20 July 2018
    * @ShortDescription      Add multiple Absent date.
    * @param                 Object $request This contains full request 
    * @return                Array
    */ 
    public function add_absent(Request $request)
    {
        $requestData =$this->security_lib_obj->decryptInput($request);
        $errors = [];
        // Validate request
        if(!empty($requestData['absent_type'])){
            $requestData['absent_type']= strtolower($requestData['absent_type']);
        }
        $validator = Validator::make($requestData, [
            'absent_date.*' =>  'required|date_format:"Y-m-d"',
            'absent_type'   =>  'required|string|in:both,pickup,drop',
            'student_id'    =>  'required|numeric'
        ]);
        if($validator->fails()){
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.student_absent_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $validator = Validator::make($requestData, [
            'student_id'    =>  "required|check_unique_id:students"
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.student_absent_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $user_id = Auth::id();
        $requestData['absent_type'] = strtolower($requestData['absent_type']);
        $absent_dates = $requestData['absent_date'];
        $parameter['month'] = date('m');
        $parameter['year'] = date('Y');
        foreach ($absent_dates as $key => $date) {
            $parameter['month'] = Carbon::parse($date)->format('m');
            $parameter['year'] = Carbon::parse($date)->format('Y');
            $absent_date = Carbon::parse($date)->format('Y-m-d');

            // Check date greater than and equalto current date
            if ($absent_date > date('Y-m-d')) {
                $student_absent_model = new StudentAbsent;
                $student_absent_date  = $student_absent_model->check_student_absent_marked($user_id, $requestData['student_id'], $absent_date);
                $student_model = new Student;
                $student_details  = $student_model->get_student_details($requestData['student_id']);
                if ($student_absent_date == 0) {
                    $absent_data_array['absent_date'] = $absent_date;
                    $absent_data_array['user_id']     = $user_id;
                    $absent_data_array['student_id']  = $requestData['student_id'];
                    $absent_data_array['school_id']   = $student_details->school_id;
                    $absent_data_array['absent_type'] = $requestData['absent_type'];
                    $absent_data_array['created_by']  = $user_id;
                    $absent_data_array['updated_by']  = $user_id;
                    $absent_data_array['user_agent']  = $request->server('HTTP_USER_AGENT');
                    $absent_data_array['ip_address']  = $request->ip();
                    $absent_data_array['created_at']  = date('Y-m-d H:i:s');
                    $absent_data_array['updated_at']  = date('Y-m-d H:i:s');

                    //Insert into 
                    $result = $student_absent_model->insert($absent_data_array);
                }else{
                    return $this->resultResponse(
                        Config::get('restresponsecode.SUCCESS'), 
                        [], 
                        [],
                        trans('Parents::parent.absent_already_add'), 
                        $this->http_codes['HTTP_OK']
                    );
                }
            }else{
                $absent_add_error = 'absent_add_error';
                if ($absent_date == date('Y-m-d')) {
                    $absent_add_error = 'absent_add_current_date_error';
                }
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    [],
                    trans('Parents::parent.'.$absent_add_error), 
                    $this->http_codes['HTTP_OK']
                );
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [], 
            [],
            trans('Parents::parent.absent_successfull'), 
            $this->http_codes['HTTP_OK']
        );    
        //return $this->calendar_event($error = 0, $errorMessage = 'absent_list_error', $message = 'absent_add_successfull', $requestData = $parameter); 
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Delete Absent date one by one
    * @param                 Object $request This contains full request 
    * @return                Array
    */ 
    public function delete_absent(Request $request)
    {
        $requestData =$this->security_lib_obj->decryptInput($request);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $error = false;
        $validator = Validator::make($requestData, [
            'student_absent_id' => 'required|numeric',
            'resource_type'     => 'required|string|in:ios,android,web',
        ]);
        
        if ($validator->fails()) {
            if ($validator->fails()) {
                $errors = $validator->errors();
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    $errors,
                    trans('Parents::parent.student_absent_validation'), 
                    $this->http_codes['HTTP_OK']
                );
            }
        }

        $validator = Validator::make($requestData, [
            'student_absent_id'    =>  "required|check_unique_id:student_absents"
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.student_absent_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }
        
        $parameter =array();
        $student_absent_id = $requestData['student_absent_id'];
        $resource_type = $requestData['resource_type'];

        //check date
        $student_absent_model = new StudentAbsent;
        $student_absent_data  = $student_absent_model->get_student_absent_by_id($student_absent_id);
        if ($student_absent_data) {
            $absent_date = $student_absent_data->absent_date;

            //check date greater than and equalto current date
            if ($absent_date > date('Y-m-d')) {
                $student_absent_data->resource_type = $resource_type;
                $student_absent_data->user_agent = $user_agent;
                $student_absent_data->ip_address = $request->ip();

                $update_student_absent_array['resource_type'] = $student_absent_data->resource_type;
                $update_student_absent_array['ip_address'] = $student_absent_data->ip_address;
                $update_student_absent_array['is_deleted'] = 1;
                $update_student_absent_array['updated_by'] = $student_absent_data->created_by;
                $update_student_absent_array['updated_at'] = date('Y-m-d H:i:s');

                $student_absent_model->update_student_absent($update_student_absent_array, $student_absent_id);
                $parameter['month'] = date('m');
                $parameter['year'] = date('Y');
                //return $this->calendar_event($error = 0, $errorMessage = 'absent_list_error', $message = 'absent_remove_successfull', $requestData = $parameter);                  
            } else {
                return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'), 
                    [], 
                    [],
                    trans('Parents::parent.absent_remove_error'), 
                    $this->http_codes['HTTP_OK']
                );
            }
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('Parents::parent.absent_already_removed'), 
                $this->http_codes['HTTP_OK']
            );
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [], 
            [],
            trans('Parents::parent.absent_successfull'), 
            $this->http_codes['HTTP_OK']
        );    
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Get Absent List
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function get_absent_list(Request $request)
    {   
        $requestData =$this->security_lib_obj->decryptInput($request);
        $validator = Validator::make($requestData, [
            'month' => 'required',
            'year' => 'required',
            'student_id' => 'required|numeric'
        ]);
        
        if($validator->fails()){
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.student_absent_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $validator = Validator::make($requestData, [
            'student_id'    =>  "required|check_unique_id:students"
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.student_absent_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }
        $user_id = Auth::id();

        //Get Default dashboard
        $student_vehicle_routes_model = new StudentVehicleRoute;
        $result1 = $student_vehicle_routes_model->student_vehicle_routes($requestData['student_id']);
        if(!empty($result1)){
            $result1->pickup_time = date("h:i A",strtotime($result1->pickup_time));
            $result1->drop_time = date("h:i A",strtotime($result1->drop_time));
        }
        
        if(empty($result1)){
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::event.passenger_dashboard_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            ); 
        }
        $result = array();

        //Default set
        $employee_type = 'driver';
        $now = Date('H:i:s');
        $pickUpbusFlag = 0;
        $dropbusFlag = 0;
        $pickup = (array) $result1;
        $drop = (array) $result1;

        //Get Stoppage Name
        $pickup['stoppage_name'] = Stoppage::find($pickup['pickup_stoppage'])->stoppage_name;
        $drop['stoppage_name'] = Stoppage::find($drop['drop_stoppage'])->stoppage_name;
        unset($pickup['drop_time'],$pickup['drop_stoppage']);
        $result['pickup'] = array_merge($pickup, array('absent_type'=>'pickup', 'absent_date'=>"",'student_absent_id'=>0));
       

        unset($drop['pickup_time'],$drop['pickup_stoppage']);
        $result['drop'] = array_merge($drop, array('absent_type'=>'drop', 'absent_date'=>"",'student_absent_id'=>0));
  
        
        
        $blankArg = array();
        $data = array();
        //Check Absent Dates
        $student_absent_model = new StudentAbsent;
        $student_absent_list  = $student_absent_model->get_single_student_absents($user_id, $requestData['year'],$requestData['month'], $requestData['student_id']);

        if($student_absent_list){
            foreach ($student_absent_list as $value1) {
                $value1 = (array) $value1;
                $absent_type = $value1['absent_type'];
                foreach ($result as $value2) {
                    if($absent_type == 'both'){
                        $res[]=$value1+$blankArg;
                        break;
                    }else if($absent_type == $value2['absent_type']){                    
                        $res[]=$value1+$value2;
                    }else {                    
                        $res[]=array_merge($value2, array('absent_type'=>$value2['absent_type'], 'absent_date'=>$value1['absent_date'],'student_absent_id'=>0));
                    }
                }
            }
            foreach ($res as $value3) {            
                $data[$value3['absent_date']][$value3['absent_type']] = $value3;
            }      
            
            foreach ($data as  $key1 => $value4) {
                if(!array_key_exists('both', $value4)) {
                    $data[$key1]['both'] = "";
                }
                if(!array_key_exists('pickup', $value4)) {
                    $data[$key1]['pickup'] = "";
                }
                if(!array_key_exists('drop', $value4)) {
                    $data[$key1]['drop'] = "";
                }
            }
        }
        //Default date set
        
        $result['both'] = "";

        $month = $requestData['month'];
        $year = $requestData['year'];
        $days = cal_days_in_month(CAL_GREGORIAN,$month,$year);
        //Calendar blank date data 
       
        for( $i = 1; $i<=$days; $i++ ) {
            $i = str_pad($i, 2, "0", STR_PAD_LEFT);
            if(!array_key_exists($year.'-'.$month.'-'.$i, $data)) {
                $data[$year.'-'.$month.'-'.$i] = $result;
                $data[$year.'-'.$month.'-'.$i]['pickup']['absent_date'] = $year.'-'.$month.'-'.$i;
                $data[$year.'-'.$month.'-'.$i]['drop']['absent_date'] = $year.'-'.$month.'-'.$i;
            }
        } 
        
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [$data],
            [],
            trans('Parents::parent.absent_successfull'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      This is used for Calendar Events list
    * @return                Array
    */    
    private function calendar_event($error = 0, $errorMessage = 'absent_list_error', $message = '', $requestData = array())
    {
        $user_id = Auth::id();
        $error = false;
        $validator = Validator::make($requestData, [
            'month' => 'required',
            'year' => 'required',
        ]);
        
        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }

        if ($error == true) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.student_absent_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $month = $requestData['month'];
        $year = $requestData['year'];
        $days = cal_days_in_month(CAL_GREGORIAN,$month,$year);

        //Check Absent Dates
        $student_absent_model = new StudentAbsent;
        $student_absent_list  = $student_absent_model->get_student_absents($user_id, $year, $month);

        //Merge two array
        $student_absent_array = array();
        if ($student_absent_list) {
            foreach ($student_absent_list as $key => $data) {
                $student_absent_array[$key]['student_absent_id'] = $data->student_absent_id;
                $student_absent_array[$key]['student_id'] = $data->student_id;
                $student_absent_array[$key]['school_id'] = $data->school_id;
                $student_absent_array[$key]['absent_date'] = $data->absent_date;
                $student_absent_array[$key]['absent_type'] = $data->absent_type;
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [$student_absent_array], 
            [],
            trans('Parents::parent.absent_successfull'), 
            $this->http_codes['HTTP_OK']
        );    
    }
}