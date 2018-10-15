<?php
namespace App\Modules\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Config;
use Mail;
use DB;
use Carbon\Carbon;
use App\Libraries\SecurityLib;
use App\Libraries\FileLib;
use App\Libraries\CsvLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Models\Stoppage;
use App\Models\Student;
use App\Models\Vehicle;
use App\Models\StudentVehicleRoute;
use App\Models\EmployeeVehicle;
use App\Models\VehicleRoute;
use App\Models\Routes;
use App\Models\Device;
use App\Models\DeviceAllocation;
use App\Models\RouteStoppages;
use Geocode;
use Excel;
/**
 * BusManagementController Class
 *
 * @package                Education
 * @subpackage             MasterController
 * @category               Controller
 * @DateOfCreation         26 July 2018
 * @ShortDescription       This controller perform all bus management related functionality for admin api
 */

class BusManagementController extends Controller
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

        // Init CSV Library object
        $this->csvLibObj  = new CsvLib(); 

           // Init File Library object
        $this->FileLib    = new FileLib();

    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      vehicle list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function get_vehicle_list(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new Vehicle;
        $list  = $user_model->get_vehicles_list_dropdown($requestData);
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Student allocation list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function student_allocation(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new StudentVehicleRoute;
        $list  = $user_model->get_student_allocation($requestData);
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Staff allocation list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function staff_allocation(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new EmployeeVehicle;
        $list  = $user_model->get_staff_allocation($requestData);
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Route allocation list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function route_allocation(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new VehicleRoute;
        $list  = $user_model->get_route_allocation($requestData);
        
        foreach($list['data'] as &$value)
        {
            $value->start_time = date("g:i A", strtotime($value->start_time));
            $value->end_time = date("g:i A", strtotime($value->end_time));
        }
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Get stoppage list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function stoppage_list(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new RouteStoppages;
        $list  = $user_model->get_stoppage_list($requestData);
        $start_time = $requestData['start_time'];
        $arrival_time = $start_time;
        $i = 1;
        foreach ($list as &$value) {
            if($i == 1)
            {
                $value->duration = $start_time;
                $i++;
            }
            else
            {
                $arrival_time = date("h:i A", strtotime("+$value->duration minutes", strtotime($arrival_time)));
                $value->duration = $arrival_time;
            }
        }
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Get route list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function route_list(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new Routes;
        $list  = $user_model->get_route_list($requestData);
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Get route stoppage list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function route_stoppage(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $user_model = new RouteStoppages;
        $list  = $user_model->get_route_stoppages($requestData);
        // validate, is query executed successfully
        if ($list) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $list,
                [],
                trans('Admin::messages.success'),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Admin::messages.error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation      27 July 2018
     * @ShortDescription    Custom Export studentAllocation file.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function export_studentAllocation($user_id)
    {
        //Decrypt user_id
        $user_id = $this->security_lib_obj->decrypt(base64_decode($user_id));
        $school_id = User::find($user_id)->school_id;

        $headers = ["Student Vehicle Route ID", "Route ID", "Vehicle ID", "Start Time", "Student ID", "Pickup Stoppage ID", "Pickup Time", "Drop Stoppage ID", "Drop Time", "Delete"];
        $extraInfo = [
            'sheetTitle' => 'export_student_allocation',
            'sheetName'  => 'export_student_allocation'
        ];
        $headerArray =array();
        $downloadFileName = "export_student_allocation"; 
        $downloadType = "csv";

        $selectData  =  [ 
                          'student_vehicle_routes.student_vehicle_route_reference as student_route_id',
                          DB::raw("(SELECT route_reference FROM routes WHERE routes.route_id = student_vehicle_routes.route_id) as Route_ID"),
                          DB::raw("(SELECT vehicle_reference FROM vehicles WHERE vehicles.vehicle_id = student_vehicle_routes.vehicle_id) as Vehicle_ID"),
                          "vehicle_routes.start_time",
                          DB::raw("(SELECT student_reference FROM students WHERE students.student_id = student_vehicle_routes.student_id) as student_id"),
                          DB::raw("(SELECT stoppage_reference FROM stoppages WHERE stoppages.stoppage_id = student_vehicle_routes.stoppage_pickup) as stoppage_pickup"),
                          'student_vehicle_routes.pickup_time',
                          DB::raw("(SELECT stoppage_reference FROM stoppages WHERE stoppages.stoppage_id = student_vehicle_routes.stoppage_drop) as stoppage_drop"),
                          'student_vehicle_routes.drop_time',
                          'student_vehicle_routes.is_deleted'
                        ];
        $whereData   =  [
                        'student_vehicle_routes.school_id'=> $school_id,
                        'student_vehicle_routes.is_deleted'=>  0
                        ];

        $details =  DB::table('student_vehicle_routes')
                        ->select($selectData)
                        ->where($whereData)
                        ->join('students', 'student_vehicle_routes.student_id', '=', 'students.student_id')
                        ->join('vehicle_routes', function($join)
                               {
                                  $join->on('vehicle_routes.vehicle_id', '=', 'student_vehicle_routes.vehicle_id')
                                  ->on('vehicle_routes.route_id', '=', 'student_vehicle_routes.route_id');
                               })
                        ->get()->toArray();
        if(!empty($details)){
            foreach ($details as $key => $value) {
                $headerArray[$key][] =   $value->student_route_id;
                $headerArray[$key][] =   $value->route_id;
                $headerArray[$key][] =   $value->vehicle_id;
                $headerArray[$key][] =   $value->start_time;
                $headerArray[$key][] =   $value->student_id;
                $headerArray[$key][] =   $value->stoppage_pickup;
                $headerArray[$key][] =   $value->pickup_time;
                $headerArray[$key][] =   $value->stoppage_drop;
                $headerArray[$key][] =   $value->drop_time;
                $headerArray[$key][] =   $value->is_deleted;
            }
        }
        return $this->csvLibObj->exportBlankData($headerArray, $headers, $downloadFileName, $downloadType, $extraInfo);
    }

    /**
     * @DateOfCreation      27 July 2018
     * @ShortDescription    Custom Export staff allocation file.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function export_staffAllocation($user_id)
    {
        //Decrypt user_id
        $user_id = $this->security_lib_obj->decrypt(base64_decode($user_id));
        $school_id = User::find($user_id)->school_id;

        $headers = ["Employee Vehicle ID", "Bus ID", "Effective Date", "Driver ID", "Assistant ID", "Delete"];
        $extraInfo = [
            'sheetTitle' => 'export_staff_allocation',
            'sheetName'  => 'export_staff_allocation'
        ];
        $headerArray =array();
        $downloadFileName = "export_staff_allocation"; 
        $downloadType = "csv";
        $selectData  =  [ 
                          'employee_vehicles.employee_vehicle_reference',
                          'vehicles.vehicle_reference',
                          'employee_vehicles.effective_date',
                          DB::raw("(SELECT user_reference FROM users WHERE users.user_id = employee_vehicles.user_driver_id) as driver_reference"),
                          DB::raw("(SELECT user_reference FROM users WHERE users.user_id = employee_vehicles.user_assistant_id) as assistant_reference"),
                          'employee_vehicles.is_deleted'
                        ];
        $whereData   =  [
                        'employee_vehicles.school_id'=> $school_id,
                        'employee_vehicles.is_deleted'=>  0
                        ];

        $details =  DB::table('employee_vehicles')
                        ->select($selectData)
                        ->where($whereData)
                        ->join('vehicles', 'employee_vehicles.vehicle_id', '=', 'vehicles.vehicle_id')
                        ->get()->toArray();
        if(!empty($details)){
            foreach ($details as $key => $value) {
                $headerArray[$key][] =   $value->employee_vehicle_reference;
                $headerArray[$key][] =   $value->vehicle_reference;
                $headerArray[$key][] =   $value->effective_date;
                $headerArray[$key][] =   $value->driver_reference;
                $headerArray[$key][] =   $value->assistant_reference;
                $headerArray[$key][] =   $value->is_deleted;
            }
        }

        return $this->csvLibObj->exportBlankData($headerArray, $headers, $downloadFileName, $downloadType, $extraInfo);
    }

    /**
     * @DateOfCreation      27 July 2018
     * @ShortDescription    Save student list.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function student_import_save(Request $request)
    {   
        $user_id = Auth::id();
        $requestData = $request->all();
        $requestDefault['school_id'] = User::find($user_id)->school_id;
        $destination = 'csv/';
        $fileUpload = $this->FileLib->fileUpload($requestData['import'], $destination);
        $csvStudentData = $this->csvLibObj->importDataFromSaved($destination.$fileUpload['uploaded_file']);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $requestDefault['updated_by'] = $user_id;
        $requestDefault['resource_type'] = 'web';
        $requestDefault['user_agent'] = $user_agent;
        $requestDefault['ip_address'] = $request->ip();    
        $requestDefault['updated_at'] = date('Y-m-d H:i:s');
        $student_model = new Student;
        $user_model = new User;
        foreach ($csvStudentData['result'] as $column) {
            $column['student_reference'] = intval($column['student_reference']);
            $student_reference = $column['student_reference'];
            $student_data = $student_model->get_student_details_by_reference_id($student_reference, $requestDefault['school_id']);
            $student_array = array();
            if (!empty($student_data)) {
                if ($column['delete'] == 'true' OR $column['delete'] == 'TRUE') {
                    // Soft delete student
                    $student_array['is_deleted'] = 1;
                    $student_array['updated_at'] = date('Y-m-d H:i:s');
                    $student_array = array_merge($student_array, $requestDefault);
                    $student_model->update_student($student_data->student_id,$student_array);
                } else {

                    // Student update
                    $parent_data = $user_model->get_user_detail_by_reference($column['parent_id'], $requestDefault['school_id']);
                    $column['parent_id'] = $parent_data->user_id;
                    $student_array = array_merge($column, $requestDefault);
                    // Get student data
                    $student_id = $student_data->student_id;

                    $student_array['name'] = $student_array['student_name'];
                    unset($student_array['parent_id']);
                    unset($student_array['delete']);
                    unset($student_array['student_name']);
                    $student_model->update_student($student_id,$student_array);

                    // Student parents update
                    $student_parents_array['user_id']    = $column['parent_id'];
                    $student_parents_array['updated_at'] = date('Y-m-d H:i:s');
                    $student_parents_array['student_id'] = $student_id;
                    $student_parents_array = array_merge($student_parents_array, $requestDefault);
                    $student_model->update_student_parent($student_id, $student_parents_array);
                }
            } else {

                // Insert student
                $requestDefault['created_at'] = date('Y-m-d H:i:s');    
                $requestDefault['created_by'] = $user_id;
                $merge = array_merge($column, $requestDefault);
                $merge['name'] = $merge['student_name'];
                unset($merge['student_name']);
                unset($merge['parent_id']);
                unset($merge['delete']);
                unset($merge['student_id']);
                DB::table('students')->insert($merge);
                $lastInsertedId = app('db')->getPdo()->lastInsertId();

                // Insert student parent
                $student_parents['school_id']     = $requestDefault['school_id'];
                $parent_data = $user_model->get_user_detail_by_reference($column['parent_id'], $requestDefault['school_id']);
                $column['parent_id'] = $parent_data->user_id;
                $student_parents['user_id']       = $column['parent_id'];
                $student_parents['student_id']    = $lastInsertedId;
                $student_parents['created_by']    = $user_id;
                $student_parents['updated_by']    = $user_id;
                $student_parents['resource_type'] = 'web';
                $student_parents['user_agent']    = $user_agent;
                $student_parents['ip_address']    = $request->ip();
                $student_parents['created_at']    = date('Y-m-d H:i:s');    
                $student_parents['updated_at']    = date('Y-m-d H:i:s');
                DB::table('student_parents')->insert($student_parents);
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [],
            [],
            trans('Admin::messages.import_successfull'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
     * @DateOfCreation      30 July 2018
     * @ShortDescription    Stoppage location.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function stoppage_import_save(Request $request)
    {   
        $user_id = Auth::id();
        $requestData = $request->all();
        $destination = 'csv/';
        $fileUpload = $this->FileLib->fileUpload($requestData['import'], $destination);
        $csvStoppageData = $this->csvLibObj->importDataFromSaved($destination.$fileUpload['uploaded_file']);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $requestDefault['resource_type'] = 'web';
        $requestDefault['user_agent'] = $user_agent;
        $requestDefault['ip_address'] = $request->ip();  
        $requestDefault['school_id'] = User::find($user_id)->school_id;
        $stoppage_model = new Stoppage;
        foreach ($csvStoppageData['result'] as $column) {
            $stoppageData = $stoppage_model->get_stoppage_id_by_reference_id($column['stoppage_reference'], $requestDefault['school_id']);
            $response = Geocode::make()->address($column['stoppage_address']);
            if ($response) {
                $requestDefault['stoppage_latitude']= $response->latitude();
                $requestDefault['stoppage_longitude']= $response->longitude();
            }
            if(!empty($stoppageData)){
                //Update
                if ($column['delete'] == 'true' OR $column['delete'] == 'TRUE') {
                    unset($column['delete']);

                    //Update
                    $requestDefault['is_deleted'] = 1;
                    $requestDefault['updated_by'] = $user_id;
                    $requestDefault['updated_at'] = date('Y-m-d H:i:s');
                    $data = array_merge($column, $requestDefault);
                    DB::table('stoppages')
                        ->where('stoppage_id', $stoppageData->stoppage_id)
                        ->update($data);
                }else{
                    $requestDefault['updated_by'] = $user_id;
                    $requestDefault['updated_at'] = date('Y-m-d H:i:s');
                    //Update
                    $data = array_merge($column, $requestDefault);
                    $data['is_deleted'] = 0;
                    unset($data['delete']);
                    DB::table('stoppages')
                        ->where('stoppage_id', $stoppageData->stoppage_id)
                        ->update($data);
                }
            }else{
                //Insert
                $requestDefault['created_by'] = $user_id;
                $requestDefault['created_at'] = date('Y-m-d H:i:s');
                $merge = array_merge($column, $requestDefault);
                unset($merge['delete']);
                DB::table('stoppages')->insert($merge);
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [],
            [],
            trans('Admin::messages.import_successfull'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
     * @DateOfCreation      30 July 2018
     * @ShortDescription    Stoppage location.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function import_student_allocation(Request $request)
    {   
        $user = Auth::user();
        $user_id = $user->user_id;
        $school_id = $user->school_id;
        $requestData = $request->all();
        $destination = 'csv/';
        $fileUpload = $this->FileLib->fileUpload($requestData['import'], $destination);
        $csvStudentAllocationData = $this->csvLibObj->importDataFromSaved($destination.$fileUpload['uploaded_file']);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $requestDefault['resource_type'] = 'web';
        $requestDefault['user_agent'] = $user_agent;
        $requestDefault['ip_address'] = $request->ip();  
        $requestDefault['school_id'] = User::find($user_id)->school_id;

        // Object initialization for models
        $stoppage_model = new Stoppage;
        $vehicle_route_model = new VehicleRoute;
        $student_model = new Student;
        $st_vehicle_route_model = new StudentVehicleRoute;
        foreach ($csvStudentAllocationData['result'] as $column) {
            $route_reference = $column['route_id'];
            $vehicle_reference = $column['vehicle_id'];
            $student_reference = $column['student_id'];
            $pick_stoppage_reference = $column['pickup_stoppage_id'];
            $drop_stoppage_reference = $column['drop_stoppage_id'];
            $vehicle_routes = $vehicle_route_model->get_vehicle_route_id_for_student(array( "school_id" => $school_id,
                                                                                            "vehicle_reference" => $vehicle_reference,
                                                                                            "route_reference" => $route_reference
                                                    ));
            $student_details = $student_model->get_student_details_by_reference_id($student_reference, $school_id);
            if(!empty($vehicle_routes) AND !empty($student_details)){
                $pick_stoppage = $stoppage_model->get_stoppage_id_by_reference_id($pick_stoppage_reference, $school_id);
                $drop_stoppage = $stoppage_model->get_stoppage_id_by_reference_id($drop_stoppage_reference, $school_id);
                $check_student_vehicle_route = $st_vehicle_route_model->check_student_route_allocation(array("school_id"=>$school_id, "student_id" => $student_details->student_id));
                if(!empty($check_student_vehicle_route)){
                    $st_vehicle_route_model->add_vehicle_route( array("student_id" => $student_details->student_id,
                                                                     "vehicle_id" => $vehicle_routes->vehicle_id,
                                                                     "route_id" => $vehicle_routes->route_id,
                                                                     "stoppage_pickup" => $pick_stoppage->stoppage_id,
                                                                     "school_id" => $school_id,
                                                                     "pickup_time" => $column['pickup_time'],
                                                                     "stoppage_drop" => $drop_stoppage->stoppage_id,
                                                                     "drop_time" => $column['drop_time'],
                                                                     "is_deleted" => $column['delete'] == true ? 1: 0 ,
                                                                     "created_by" => $user_id,
                                                                     "updated_by" => $user_id,
                                                                     "vehicle_route_id" => $vehicle_routes->vehicle_route_id,
                                                                     "start_time" => $column['start_time']
                                                                 ),
                                                                array("student_vehicle_route_id" => $check_student_vehicle_route->student_vehicle_route_id),
                                                                2
                                                            );
                }else{
                    $st_vehicle_route_model->add_vehicle_route(array("student_id" => $student_details->student_id,
                                                                     "vehicle_id" => $vehicle_routes->vehicle_id,
                                                                     "route_id" => $vehicle_routes->route_id,
                                                                     "stoppage_pickup" => $pick_stoppage->stoppage_id,
                                                                     "school_id" => $school_id,
                                                                     "pickup_time" => $column['pickup_time'],
                                                                     "stoppage_drop" => $drop_stoppage->stoppage_id,
                                                                     "drop_time" => $column['drop_time'],
                                                                     "is_deleted" => 0 ,
                                                                     "created_by" => $user_id,
                                                                     "updated_by" => $user_id,
                                                                     "vehicle_route_id" => $vehicle_routes->vehicle_route_id,
                                                                     "start_time" => $column['start_time'] )
                                                                );
                }
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [],
            [],
            trans('Admin::messages.import_successfull'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
     * @DateOfCreation      08 August 2018
     * @ShortDescription    Schedule route import.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function import_schedule_route_save(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $request->all();
        
        $destination = 'csv/';
        $file_upload = $this->FileLib->fileUpload($request_data['import'], $destination);
        $csv_data = $this->csvLibObj->importDataFromSaved($destination.$file_upload['uploaded_file']);
        $user_agent = $request->server('HTTP_USER_AGENT');
        
        $user_model = new User;
        $vehicle_model = new Vehicle;
        $vehicle_route_model = new VehicleRoute;
        $route_model = new Routes;
        $device_model = new Device;
        $device_vehicle_model = new DeviceAllocation;
        $employee_vehicle_model = new EmployeeVehicle;
        
        $request_default['school_id'] = $user_model->find($user_id)->school_id;
        $request_default['resource_type'] = 'web';
        $request_default['user_agent'] = $user_agent;
        $request_default['ip_address'] = $request->ip();  

        foreach ($csv_data['result'] as $key => $column) {
            $vehicle_name = trim($column['bus_name'], ' ');
            $route_name = trim($column['route_name'], ' ');
            $user_driver_reference = trim($column['driver'], ' ');
            $user_assistant_reference = trim($column['assistant'], ' ');

            $vehicle_data = $vehicle_model->get_vehicle_data($vehicle_name, $request_default['school_id']);

            $route_data = $route_model->get_route_data($route_name, $request_default['school_id']);

            $user_driver_data = $user_model->get_user_data_by_type($user_driver_reference, $request_default['school_id'], 'driver');

            $user_assistant_data = $user_model->get_user_data_by_type($user_assistant_reference, $request_default['school_id'], 'assistant');
            
            $device_data = $device_model->get_device_data_by_reference($column['device'], $request_default['school_id']);

            $shift = strtolower($column['shift']);
            $vehicle_route_data = $vehicle_route_model->get_vehicle_route_data($vehicle_data->vehicle_id, $route_data->route_id, $shift, $request_default['school_id']);

            $vehicle_route_array = array();
            $device_vehicle_array = array();
            $employee_vehicle_array = array();
            if (!empty($vehicle_route_data)) {
                if ($column['delete'] == 'true' OR $column['delete'] == 'TRUE') {
                    // Soft delete vehicle table.
                    $vehicle_route_array['is_deleted'] = 1;
                    $vehicle_route_array['updated_by'] = $user_id;
                    $vehicle_route_array['updated_at'] = date('Y-m-d H:i:s');
                    $vehicle_route_model->update_vehicle_route($vehicle_route_array, $vehicle_route_data->vehicle_route_id, $request_default['school_id']);

                    // Soft delete vehicle table.
                    $employee_vehicle_array['is_deleted'] = 1;
                    $employee_vehicle_array['updated_by'] = $user_id;
                    $employee_vehicle_array['updated_at'] = date('Y-m-d H:i:s');
                    $employee_vehicle_model->update_employee_vehicle_csv_data($employee_vehicle_array,$vehicle_data->vehicle_id,$request_default['school_id']);

                    // Soft delete device table.
                    $device_vehicle_array['is_deleted'] = 1;
                    $device_vehicle_array['updated_by'] = $user_id;
                    $device_vehicle_array['updated_at'] = date('Y-m-d H:i:s');
                    $device_vehicle_model->update_device_vehicle_csv_data($device_vehicle_array, $device_data->device_id, $request_default['school_id']);

                }else{
                    $request_default['updated_by'] = $user_id;

                    // Update vehicle route.
                    $vehicle_route_array['vehicle_id'] = $vehicle_data->vehicle_id;
                    $vehicle_route_array['route_id']   = $route_data->route_id;

                    $start_time = date("H:i", strtotime($column['start_time']));
                    $end_time = date("H:i", strtotime($column['end_time']));
                    $vehicle_route_array['start_time'] = $start_time;
                    $vehicle_route_array['end_time'] = $end_time;
                    $vehicle_route_array['shift'] = $shift;
                    $request_default['updated_at'] = date('Y-m-d H:i:s');
                    $vehicle_route_array = array_merge($vehicle_route_array, $request_default);
                    $vehicle_route_model->update_vehicle_route($vehicle_route_array, $vehicle_route_data->vehicle_route_id, $request_default['school_id']);

                    // Employee vehicles.
                    $employee_vehicle_array['user_driver_id'] = $user_driver_data->user_id;
                    $employee_vehicle_array['user_assistant_id'] = $user_assistant_data->user_id;
                    $request_default['updated_at'] = date('Y-m-d H:i:s');
                    $employee_vehicle_array = array_merge($employee_vehicle_array, $request_default);
                    $employee_vehicle_model->update_employee_vehicle_csv_data($employee_vehicle_array,$vehicle_data->vehicle_id,$request_default['school_id']);

                    // Update device vehicle.
                    $device_vehicle_array['device_id']  = $device_data->device_id;
                    $device_vehicle_array['vehicle_id'] = $vehicle_data->vehicle_id;
                    $request_default['updated_at']    = date('Y-m-d H:i:s');
                    $device_vehicle_array = array_merge($device_vehicle_array, $request_default);
                    $device_vehicle_model->update_device_vehicle_csv_data($device_vehicle_array, $device_data->device_id, $request_default['school_id']);
                }
            } else {
                $request_default['created_by'] = $user_id;

                // Insert vehicle route.
                $vehicle_route_array['vehicle_id'] = $vehicle_data->vehicle_id;
                $vehicle_route_array['route_id']   = $route_data->route_id;
                $start_time = date("H:i", strtotime($column['start_time']));
                $end_time = date("H:i", strtotime($column['end_time']));
                $vehicle_route_array['start_time'] = $start_time;
                $vehicle_route_array['end_time'] = $end_time;
                $vehicle_route_array['shift'] = $shift;
                $request_default['created_at'] = date('Y-m-d H:i:s');
                $request_default['updated_at'] = date('Y-m-d H:i:s');
                $vehicle_route_array = array_merge($vehicle_route_array, $request_default);
                $vehicle_route_model->insert_vehicle_route($vehicle_route_array);

                // Insert Employee Vehicles.
                $employee_vehicle_array['user_driver_id']    = $user_driver_data->user_id;
                $employee_vehicle_array['user_assistant_id'] = $user_assistant_data->user_id;
                $employee_vehicle_array['vehicle_id'] = $vehicle_data->vehicle_id;
                $employee_vehicle_array['effective_date'] = date('Y-m-d H:i:s');
                $request_default['created_at'] = date('Y-m-d H:i:s');
                $request_default['updated_at'] = date('Y-m-d H:i:s');
                $employee_vehicle_array = array_merge($employee_vehicle_array, $request_default);
                $employee_vehicle_model->insert_employee_vehicle($employee_vehicle_array);

                // Insert Device Vehicles.
                $device_vehicle_array['device_id']    = $device_data->device_id;
                $device_vehicle_array['vehicle_id'] = $vehicle_data->vehicle_id;
                $request_default['created_at'] = date('Y-m-d H:i:s');
                $request_default['updated_at'] = date('Y-m-d H:i:s');
                $device_vehicle_array = array_merge($device_vehicle_array, $request_default);
                $device_vehicle_model->insert_device_vehicle($device_vehicle_array);
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [],
            [],
            trans('Admin::messages.import_successfull'),
            $this->http_codes['HTTP_OK']
        );        
    }

    /**
     * @DateOfCreation        09 August 2018
     * @ShortDescription      This function is used for validating schedule route.
     * @return                Array of status and message
     */
    public function csv_validation_schedule_route(Request $request) {

        $request_data = $request->all();
        $user_id = Auth::id();
        $school_id = User::find($user_id)->school_id;

        // Check csv format.
        $validate = $this->csvLibObj->csvValidator($request_data);
        if ($validate["error"]) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                $validate['errors'],
                trans('CSVImportExport::import.import_csv_file_check'),
                $this->http_codes['HTTP_OK']
            );
        }

        // Store Csv file.
        $destination = 'csv/';
        $file_upload = $this->FileLib->fileUpload($request_data['import'], $destination);

        // Fetch data from excel.
        $path = $destination.$file_upload['uploaded_file'];
        $data = Excel::load(storage_path($path), function($reader) {
            $reader->noHeading();
        })->toArray();

        // Remove blank rows.
        if (!empty($data)) {
            $empty_row_array = array();
            $count = count($data[0]);
            foreach ($data as $mainkey => $rows) {
                $count_blank = 0;
                if ($mainkey > 0) {
                    foreach ($rows as $key => $data_row) {
                        if($data_row == null) {
                            $count_blank++;
                        }
                    }
                }
                if ($count == $count_blank) {
                    $empty_row_array[] = $mainkey+1;
                }
            }
            if (!empty($empty_row_array)) {
                $empty_row_array = implode(',', $empty_row_array);
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('Admin::messages.import_route_stoppage_empty_row_message').$empty_row_array,
                    $this->http_codes['HTTP_OK']
                );
            }
        }

        // Csv heading check.
        $data = array_filter($data);
        $csv_heading_data = reset($data);
        if (!empty($csv_heading_data)) {
            $default_heading_data   = Config::get('importConstants.schedule_route_validation');
            $default_heading_data = json_decode($default_heading_data, true);

            // Check heading count.
            $default_count_data     = count($default_heading_data);
            $csv_heading_count_data = count($csv_heading_data);
            if($default_count_data != $csv_heading_count_data) {
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('CSVImportExport::import.import_heading_count'),
                    $this->http_codes['HTTP_OK']
                );
            }

            // Check heading name.
            $heading_name_check = array();
            foreach ($default_heading_data as $key => $heading_name) {
                $heading_name = trim($heading_name, ' ');
                if (!in_array($heading_name, $csv_heading_data)) {
                    $heading_name_check[] = $heading_name;
                }
            }
            if (!empty($heading_name_check)) {
                $heading_name_check = implode(', ', $heading_name_check);
                return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('CSVImportExport::import.import_heading_name_message').$heading_name_check,
                    $this->http_codes['HTTP_OK']
                );
            }
            $error_heading_sequence = false;
            foreach ($default_heading_data as $key => $value) {
                if($value != $csv_heading_data[$key]) {
                    $error_heading_sequence = true;
                }
                if($error_heading_sequence) {
                    return $this->resultResponse(
                        Config::get('restresponsecode.ERROR'),
                        [],
                        [],
                        trans('CSVImportExport::import.import_heading_sequence_message'),
                        $this->http_codes['HTTP_OK']
                    );
                }
            }
        }

        // Check unique.
        $column_array = array();
        $column_array_duplicate = array();
        foreach ($data as $key => $value) {
            if($key > 0) {
                $column = $value[0].' '.$value[1].' '.$value[2];
                if (!in_array($column, $column_array)) {
                    $column_array[] = $column;
                } else {
                    $column_array_duplicate[] = $key+1;
                }
            }
        }
        $column_array_duplicate = implode(',',$column_array_duplicate);
        if (!empty($column_array_duplicate)) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('CSVImportExport::import.import_schedule_unique_check_message').$column_array_duplicate,
                $this->http_codes['HTTP_OK']
            );
        }

        // Check csv data.
        $delete_count = 0;
        $update_count = 0;
        $insert_count = 0;
        $error = false;
        foreach ($data as $main_key => $value) {
            $validate_message = array();
            if ($main_key > 0) {
                $data_validation_array['vehicle_name'] = trim($value[0], ' ');
                $data_validation_array['route_name'] = trim($value[1], ' ');
                $data_validation_array['shift'] = trim($value[2], ' ');
                $data_validation_array['user_driver_reference'] = trim($value[3], ' ');
                $data_validation_array['user_assistant_reference'] = trim($value[4], ' ');
                $data_validation_array['device_reference'] = trim($value[5], ' ');
                $value[6] = trim($value[6], ' ');
                if(empty($value[6])) {
                    $start_time = null;
                } else {
                    $start_time = trim($value[6], ' ');
                }

                if(empty($value[7])) {
                    $end_time = null;
                } else {
                    $end_time = trim($value[7], ' ');
                }

                $data_validation_array['start_time'] = $start_time;
                $data_validation_array['end_time'] = $end_time;
                // Check bus name exits or not.
                $vehicle_name  = trim($value[0], ' ');
                $validation_rules_array['vehicle_name'] = "required|check_vehicle_name:$vehicle_name,$school_id";
                $validation_rule_message_array['vehicle_name.required'] = 'import_vehicle_name_required_message';
                $validation_rule_message_array['vehicle_name.check_vehicle_name'] = 'import_vehicle_name_exist_message';

                // Check route name exits or not.
                $route_name = trim($value[1], ' ');
                $validation_rules_array['route_name'] = "required|check_route_name:$route_name,$school_id";
                $validation_rule_message_array['route_name.required'] = 'import_route_name_required_message';
                $validation_rule_message_array['route_name.check_route_name'] = 'import_route_name_exist_message';

                // Check shift.
                $shift = trim($value[2], ' ');
                $validation_rules_array['shift'] = "required";
                $validation_rule_message_array['shift.required'] = 'import_shift_required_message';

                // Check user of type driver exits or not.
                $user_driver_reference = trim($value[3], ' ');
                $validation_rules_array['user_driver_reference'] = "required|check_user_driver:$user_driver_reference,$school_id,driver";
                $validation_rule_message_array['user_driver_reference.required'] = 'import_user_driver_required_message';
                $validation_rule_message_array['user_driver_reference.check_user_driver'] = 'import_user_driver_exists_message';

                $user_assistant_reference = trim($value[4], ' ');
                $validation_rules_array['user_assistant_reference'] = "required|check_user_assistant:$user_assistant_reference,$school_id,assistant";
                $validation_rule_message_array['user_assistant_reference.required'] = 'import_user_assistant_required_message';
                $validation_rule_message_array['user_assistant_reference.check_user_assistant'] = 'import_user_assistant_exists_message';

                // Check device of type driver exits or not.
                $user_device_reference = trim($value[5], ' ');
                $validation_rules_array['device_reference'] = "required|check_device_reference:$user_device_reference,$school_id";
                $validation_rule_message_array['device_reference.required'] = 'import_user_device_required_message';
                $validation_rule_message_array['device_reference.check_device_reference'] = 'import_user_device_exists_message';

                // Check start time.
                $validation_rules_array['start_time'] = "required";
                $validation_rule_message_array['start_time.required'] = 'import_start_time_required_message';
                //$validation_rule_message_array['start_time.date_format_start_time_check'] = 'import_start_time_format_message';

                // Check end time.
                $validation_rules_array['end_time'] = "required";
                $validation_rule_message_array['end_time.required'] = 'import_end_time_required_message';
                //$validation_rule_message_array['end_time.date_format_end_time_check'] = 'import_end_time_format_message';

                $validator = Validator::make($data_validation_array, $validation_rules_array, $validation_rule_message_array);
                if ($validator->fails()) {
                    $errors = $validator->errors();
                    if ($errors->first('vehicle_name')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('vehicle_name')."|Bus Name";
                    }
                    if ($errors->first('route_name')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('route_name')."|Route Name";
                    }
                    if ($errors->first('shift')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('shift')."|Shift";
                    }
                    if ($errors->first('user_driver_reference')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('user_driver_reference')."|Driver";
                    }
                    if ($errors->first('user_assistant_reference')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('user_assistant_reference')."|Assistant";
                    }
                    if ($errors->first('start_time')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('start_time')."|Start Time";
                    }
                    if ($errors->first('end_time')) {
                        $error = true;
                        $error_message['error_data'][$main_key-1][] =  $main_key+1;
                        $error_message['error_data'][$main_key-1][] = $errors->first('end_time')."|End Time";
                    }
                }
                if ($error == false) {
                    $vehicle_model = new Vehicle;
                    $vehicle_data = $vehicle_model->get_vehicle_data($vehicle_name, $school_id);
                    $route_model = new Routes;
                    $route_data = $route_model->get_route_data($route_name, $school_id);

                    $shift = trim($value[2], ' ');
                    $shift = strtolower($shift);
                    $vehicle_route_model = new VehicleRoute;
                    $vehicle_route_data = $vehicle_route_model->get_vehicle_route_data($vehicle_data->vehicle_id, $route_data->route_id, $shift, $school_id);
                    if (!empty($value[8])) {
                        $delete = strtolower($value[8]);
                        if($delete === 'true') {
                            $delete_count++;    
                        }
                    }
                    if(!empty($vehicle_route_data)){
                        $update_count++;
                    }else{
                        $insert_count++;
                    }
                }
            }
        }
        if($error == true) {
            $error_count = count($error_message['error_data']);
                $k = -1;
            for ($i = 0; $i < $error_count; $i++) {
                for ($j = 0; $j < count($error_message['error_data'][$i]); $j++) {
                    if($j == 0){
                        $k++;
                        $error_message_array = explode('|',$error_message['error_data'][$i][$j+1]);
                        $error_message_data[$k] = 'Line number- '.$error_message['error_data'][$i][$j].', Column- '.$error_message_array[1].': '.trans('CSVImportExport::import.'.$error_message_array[0]);
                    } else {
                        $k++;
                        if(count($error_message['error_data'][$i]) < $j*2+1) {
                            break;
                        }
                        $error_message_array = explode('|',$error_message['error_data'][$i][$j*2+1]);
                        $error_message_data[$k] = 'Line number- '.$error_message['error_data'][$i][$j*2].', Column- '.$error_message_array[1].': '.trans('CSVImportExport::import.'.$error_message_array[0]);
                    }
                }
            }
            $error_message = implode(', ', $error_message_data);
            return $this->echoResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                $error_message,
                $this->http_codes['HTTP_OK']
            );
        } else {
            $count_array['insert_count'] = $insert_count;
            $count_array['update_count'] = $update_count;
            $count_array['delete_count'] = $delete_count;
            return $this->echoResponse(
                Config::get('restresponsecode.SUCCESS'),
                [$count_array],
                [],
                trans('CSVImportExport::import.import_list_successfull'),
                $this->http_codes['HTTP_OK']
            );
        }
    }
}