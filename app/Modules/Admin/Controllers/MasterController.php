<?php
namespace App\Modules\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Config;
use Mail;
use Carbon\Carbon;
use App\Libraries\CsvLib;
use App\Libraries\FileLib;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Models\Routes;
use App\Models\RouteStoppages;
use App\Models\Stoppage;
use App\Models\Student;
use App\Models\Vehicle;
use App\Models\Device;
use App\Models\DeviceAllocation;
use App\Models\StudentVehicleRoute;
use App\Models\VehicleRoute;
use App\Models\Beacons;
use Schema;
use Excel;
use DB;
/**
 * MasterController Class
 *
 * @package                Education
 * @subpackage             MasterController
 * @category               Controller
 * @DateOfCreation         24 July 2018
 * @ShortDescription       This controller perform all master table functionality for admin api
 */

class MasterController extends Controller
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
        $this->csvLibObj = new CsvLib();

        // Init File Library object
        $this->FileLib = new FileLib();
    }

   /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Parents list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function parents(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;
        $requestData['user_type'] = 'parent';

        $user_model = new User;
        $list  = $user_model->get_users($requestData);
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
    * @DateOfCreation        24 July 2018
    * @ShortDescription      This function used for creating and mailing password.
    * @param                 Array $request   
    * @return                Array of status and message
    */
    public function create_password(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $request->all();
        $user_agent = $request->server('HTTP_USER_AGENT');

        // Validate request
        $validate = $this->password_validator($requestData);
        if($validate["error"]) {
            return $this->echoResponse(
            Config::get('restresponsecode.ERROR'), 
            [], 
            $validate['errors'],
            trans('Admin::messages.validation'), 
            $this->http_codes['HTTP_OK']
          ); 
        }
        
        //Generate new Pasword
        $password =$this->security_lib_obj->genrateRandomPassword();        
        $requestData['password'] = bcrypt($password);
        $requestData['updated_by'] = $user_id;
        $requestData['resource_type'] = 'web';
        $requestData['user_agent'] = $user_agent;
        $requestData['ip_address'] = $request->ip();
        $user = User::updateOrCreate([
            'user_id'   => $requestData['user_id'],
        ],
            $requestData
        );
        // Send password by email
        $sent = Mail::send('emails.forgotPassword', ['name' => $user->name, 'password' => $password], function($message) use ($user) {
          $message->from(Config::get('constants.MAIL_FROM'), 'Education');
          $message->to($user->email);
          $message->subject(trans('Admin::messages.reset_password_subject'));
        });
        return $this->echoResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [], 
            [],
            trans('Admin::messages.success'), 
            $this->http_codes['HTTP_OK']
        );
    }

   /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Stoppage list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function stoppage(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $stoppage_model = new Stoppage;
        $list  = $stoppage_model->get_stoppage($requestData);
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
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Student list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function students(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $request_data['school_id'] = User::find($user_id)->school_id;

        $user_model = new Student;
        $list  = $user_model->get_student($request_data);
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
    * @DateOfCreation        25 July 2018
    * @ShortDescription      vehicle list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function vehicles(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $request_data['school_id'] = User::find($user_id)->school_id;

        $user_model = new Vehicle;
        $list  = $user_model->get_vehicles($request_data);
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
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Staff list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function staff(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;
        $requestData['user_type'] = array('driver', 'assistant');

        $user_model = new User;
        $list  = $user_model->get_staff($requestData);
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
    * @DateOfCreation        24 July 2018
    * @ShortDescription      This function used for validation.
    * @param                 Array $request   
    * @return                Array of status and message
    */
    protected function password_validator(array $data)
    {
        $error = false;
        $errors = [];
        $validator = Validator::make($data, [
            'user_id' => 'required|numeric',
        ]);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
     * @DateOfCreation      27 June 2018
     * @ShortDescription    Import location list.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function route_stoppage_import_save(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $request->all();
        $destination = 'csv/';
        $file_upload = $this->FileLib->fileUpload($request_data['import'], $destination);
        $csv_data = $this->csvLibObj->importDataFromSaved($destination.$file_upload['uploaded_file']);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $request_default['resource_type'] = 'web';
        $request_default['user_agent'] = $user_agent;
        $request_default['ip_address'] = $request->ip();    
        $request_default['school_id'] = User::find($user_id)->school_id;
        $route_model = new Routes;
        $route_stoppage_model = new RouteStoppages;
        $stoppage_model = new Stoppage;
        foreach ($csv_data['result'] as $column) {
            $stoppage_data = $stoppage_model->get_stoppage_id_by_reference_id($column['stoppage_reference'], $request_default['school_id']);
            $route_data = $route_model->get_route_details_by_route_reference_id($column['route_reference'], $request_default['school_id']);
            if (!empty($route_data)) {
                $route_stoppage_array = array();
                $route_array = array();
                if ($column['delete'] == 'true' OR $column['delete'] == 'TRUE') {
                    // Soft delete route
                    $route_array['is_deleted'] = 1;
                    $route_array['updated_at'] = date('Y-m-d H:i:s');
                    $route_array['updated_by'] = $user_id;
                    $route_array = array_merge($route_array, $request_default);
                    $route_model->update_route($route_data->route_id, $route_array);
                    // Soft delete route stoppages
                    $route_stoppage_array['is_deleted'] = 1;
                    $route_stoppage_array['updated_at'] = date('Y-m-d H:i:s');
                    $route_stoppage_array['updated_by'] = $user_id;
                    $route_stoppage_array = array_merge($route_stoppage_array, $request_default);
                    $route_stoppage_model->update_route_stoppage($route_data->route_id, $stoppage_data->stoppage_id, $request_default['school_id'],$route_stoppage_array);
                } else {
                    // route update
                    $route_array['route_reference'] = $column['route_reference'];
                    $route_array['route_name'] = $column['route_name'];
                    $route_array['updated_at'] = date('Y-m-d H:i:s');
                    $route_array['updated_by'] = $user_id;
                    $route_array = array_merge($route_array, $request_default);
                    $route_model->update_route($route_data->route_id, $route_array);

                    // Route stoppage soft delete.
                    $route_stoppage_array['is_deleted']  = 1;
                    $route_stoppage_model->update_route_stoppage($route_data->route_id, $stoppage_data->stoppage_id, $request_default['school_id'],$route_stoppage_array);

                    // Route stoppage insert.
                    $route_stoppage_array['created_by']  = $user_id;
                    $route_stoppage_array['updated_by']  = $user_id;
                    $route_stoppage_array['created_at']  = date('Y-m-d H:i:s');
                    $route_stoppage_array['updated_at']  = date('Y-m-d H:i:s');
                    $route_stoppage_array['route_id']    = $route_data->route_id;
                    $route_stoppage_array['stoppage_id'] = $stoppage_data->stoppage_id;
                    $route_stoppage_array['is_deleted']  = 0;
                    $route_stoppage_array = array_merge($route_stoppage_array, $request_default);
                    $route_id = $route_stoppage_model->insert_data($route_stoppage_array);
                }
            } else {
                // Insert to route table.
                $route_array['created_by'] = $user_id;
                $route_array['updated_by'] = $user_id;
                $route_array['created_at'] = date('Y-m-d H:i:s');
                $route_array['updated_at'] = date('Y-m-d H:i:s');
                $route_array['route_reference'] = $column['route_reference'];
                $route_array['route_name'] = $column['route_name'];
                $route_array = array_merge($route_array, $request_default);
                $route_id = $route_model->insert_data($route_array);

                // Insert to route stoppage table.
                $route_stoppage_array['route_id']    = $route_id;
                $route_stoppage_array['stoppage_id'] = $stoppage_data->stoppage_id;
                $route_stoppage_array['created_at']  = date('Y-m-d H:i:s');
                $route_stoppage_array['updated_at']  = date('Y-m-d H:i:s');
                $route_stoppage_array['created_by']  = $user_id;
                $route_stoppage_array['updated_by'] = $user_id;
                $route_stoppage_array = array_merge($route_stoppage_array, $request_default);
                $route_stoppage_model->insert_data($route_stoppage_array);
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
    * @DateOfCreation        31 July 2018
    * @ShortDescription      device list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function device(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $device_model = new Device;
        $list  = $device_model->get_device($requestData);
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
    * @DateOfCreation        31 July 2018
    * @ShortDescription      device allocation list
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function device_allocation(Request $request)
    {
        $user_id = Auth::id();
        $requestData = $this->getRequestData($request);
        $requestData['school_id'] = User::find($user_id)->school_id;

        $device_allocation_model = new DeviceAllocation;
        $list  = $device_allocation_model->get_device_allocation($requestData);
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
     * @DateOfCreation      31 July 2018
     * @ShortDescription    Device token store in database.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function device_allocation_import_save(Request $request)
    {   
        $user_id = Auth::id();
        $request_data = $request->all();
        $destination = 'csv/';
        $file_upload = $this->FileLib->fileUpload($request_data['import'], $destination);
        $csv_data = $this->csvLibObj->importDataFromSaved($destination.$file_upload['uploaded_file']);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $request_default['resource_type'] = 'web';
        $request_default['user_agent'] = $user_agent;
        $request_default['ip_address'] = $request->ip();  
        $request_default['school_id'] = User::find($user_id)->school_id;
        $device_allocation_model = new DeviceAllocation;
        $device_model = new Device;
        $vehicle_model = new Vehicle;
        foreach ($csv_data['result'] as $column) {
            $device_data = $device_model->get_device_data_by_reference($request_default['school_id'], $column['device_reference']);
            $vehicle_data = $vehicle_model->get_vehicle_data_by_reference($request_default['school_id'], $column['vehicle_reference']);
            $device_allocation_data = $device_allocation_model->get_device_vehicle_data_by_reference($request_default['school_id'], $column['device_vehicle_reference']);
            if (!empty($device_allocation_data)) {
                //Update
                if ($column['delete'] == 'true' OR $column['delete'] == 'TRUE') {
                    unset($column['delete']);

                    //Update
                    $request_default['is_deleted'] = 1;
                    $data['updated_by'] = $user_id;
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    $data = array_merge($data, $request_default);
                    $device_allocation_model->delete_data($data, $device_allocation_data->device_vehicle_id);
                }else{
                    //Update
                    $data = array_merge($column, $request_default);
                    $data['updated_by'] = $user_id;
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    $data['is_deleted'] = 0;
                    $data['device_id']  = $device_data->device_id;
                    $data['vehicle_id'] = $vehicle_data->vehicle_id;
                    unset($data['delete']);
                    unset($data['vehicle_reference']);
                    unset($data['device_reference']);
                    $device_allocation_model->update_device_vehicle($data, $device_allocation_data->device_vehicle_id);
                }
            }else{
                //Insert
                $data = array_merge($column, $request_default);
                $data['created_by'] = $user_id;
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['device_id']  = $device_data->device_id;
                $data['vehicle_id'] = $vehicle_data->vehicle_id;
                $data['is_deleted'] = 0;
                unset($data['delete']);
                unset($data['vehicle_reference']);
                unset($data['device_reference']);
                $device_allocation_model->insert_device_vehicle($data);
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

    public function checkTable($csvValidateData)
    { 
        // Check table name.
        $tableName = key($csvValidateData);
        $tableNameArr = Config::get('csvColumnConstant.tables');
        $wrongTableArr =array();
        foreach ($csvValidateData as $key => $data) {
            $table = key($data);
            if (!in_array($table, $tableNameArr)){
                $wrongTableArr[] = $table;
            }
        }
        $wrongTableArr = implode(',', $wrongTableArr);
        return $wrongTableArr;
    }

    public function checkColumn($csvValidateData) 
    {
        // Check column name.
        $wrongColumnArray = array();
        foreach ($csvValidateData as $tablekey => $tableArr) {
            $table = key($tableArr);
            foreach ($tableArr[$table] as $columnkey => $columnArr) {
                if (!Schema::hasColumn($table, $columnArr['Column'])) {
                    $wrongColumnArray[] = $columnArr['Column'];
                }
            }
        }

        $wrongColumnArray = implode(',', $wrongColumnArray);
        return $wrongColumnArray;
    }

    /**
     * @DateOfCreation      07 August 2018
     * @ShortDescription    Fetch beacon list for school.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function beacons(Request $request)
    {   
        $user = Auth::user();
        $request_data = $this->getRequestData($request);
        $user_id = $user->user_id;
        $request_data['school_id'] = $user->school_id;
        $beacon_model = new Beacons();
        $beacons_details = $beacon_model->get_beacon_list($request_data);

        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            $beacons_details,
            [],
            trans('Admin::messages.import_successfull'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
     * @DateOfCreation      08 August 2018
     * @ShortDescription    Fetch scheduled route list for school.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function schedule_route(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->getRequestData($request);
        $request_data['school_id'] = User::find($user_id)->school_id;
        $vehicleroute_model = new VehicleRoute;
        $list  = $vehicleroute_model->get_scheduled_route_list($request_data);
        foreach($list['data'] as &$value)
        {
            $value->start_time = date("h:iA", strtotime($value->start_time));
            $value->end_time = date("h:iA", strtotime($value->end_time));
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
     * @DateOfCreation      08 August 2018
     * @ShortDescription    Fetch scheduled route list for school.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function export_scheduleRoute($user_id)
    {
        //Decrypt user_id
        $user_id = $this->security_lib_obj->decrypt(base64_decode($user_id));
        $school_id = User::find($user_id)->school_id;

        $headers = ["Bus Name", "Route Name", "Shift", "Driver", "Assistant", "Device", "Start Time", "End Time", "Delete"];
        $extraInfo = [
            'sheetTitle' => 'export_schedule_route',
            'sheetName'  => 'export_schedule_route'
        ];
        $headerArray =array();
        $downloadFileName = "export_schedule_route"; 
        $downloadType = "csv";
        $select_data  =  [ 
                            'vehicles.vehicle_name',
                            'routes.route_name',
                            'vehicle_routes.shift',
                            DB::raw("(SELECT user_reference FROM users WHERE users.user_id = employee_vehicles.user_driver_id) as driver"),
                            DB::raw("(SELECT user_reference FROM users WHERE users.user_id = employee_vehicles.user_assistant_id) as assistant"),
                            "devices.device_reference",
                            'vehicle_routes.start_time',
                            'vehicle_routes.end_time',
                            'vehicle_routes.is_deleted'
                        ];
        $where_data   =  [
                            'vehicle_routes.school_id'=> $school_id,
                            'vehicle_routes.is_deleted'=>  0,
                            'vehicles.school_id' => $school_id,
                            'vehicles.is_deleted' => 0,
                            'routes.school_id' => $school_id,
                            'routes.is_deleted' => 0,
                            'employee_vehicles.school_id' => $school_id,
                            'employee_vehicles.is_deleted' => 0
                        ];

        $details =  DB::table('vehicle_routes')
                        ->select($select_data)
                        ->where($where_data)
                        ->join('vehicles', 'vehicle_routes.vehicle_id', '=', 'vehicles.vehicle_id')
                        ->join('routes', 'vehicle_routes.route_id', '=', 'routes.route_id')
                        ->join('employee_vehicles', 'vehicle_routes.vehicle_id' , "=", "employee_vehicles.vehicle_id")
                        ->join('device_vehicles', 'vehicle_routes.vehicle_id' , "=", "device_vehicles.vehicle_id")
                        ->join('devices', 'device_vehicles.device_id' , "=", "devices.device_id")
                        ->groupby("vehicles.vehicle_name", "routes.route_name", "routes.route_id", "vehicle_routes.shift", "driver", "assistant", "devices.device_reference", "vehicle_routes.start_time", "vehicle_routes.end_time", "vehicle_routes.vehicle_route_id")
                        ->get();
        if(!empty($details)){
            foreach ($details as $key => $value) {
                $headerArray[$key][] =   $value->vehicle_name;
                $headerArray[$key][] =   $value->route_name;
                $headerArray[$key][] =   $value->shift;
                $headerArray[$key][] =   $value->driver;
                $headerArray[$key][] =   $value->assistant;
                $headerArray[$key][] =   $value->device_reference;
                $headerArray[$key][] =   date("h:iA", strtotime($value->start_time));
                $headerArray[$key][] =   date("h:iA", strtotime($value->end_time));
                $headerArray[$key][] =   $value->is_deleted;
            }
        }
        return $this->csvLibObj->exportBlankData($headerArray, $headers, $downloadFileName, $downloadType, $extraInfo);
    }
}
