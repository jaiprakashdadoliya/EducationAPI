<?php

namespace App\Modules\Parents\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Libraries\SecurityLib;
use App\Traits\RestApi;
use Config;
use Illuminate\Support\Facades\Hash;
use Response;
use Carbon\Carbon;
use DB;
use stdClass;
use App\Models\User;
use App\Models\StudentAbsent;
use App\Models\StudentVehicleRoute;
use App\Models\Stoppage;
use App\Models\StudentCheckIn;
use App\Models\VehicleRoute;
use App\Models\Vehicle;
use App\Models\RouteStoppages;
use App\Models\VehicleStoppage;
use App\Models\VehicleRouteSchedules;
use App\Models\Routes;
use App\Models\Trip;
use App\Models\TripStoppage;

class DashboardController extends Controller
{
    use RestApi;

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
        $this->http_codes = $this->http_status_codes();
        // Init security library object
        $this->securityLibObj = new SecurityLib();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view("Passenger::index");
    }


    /**
    * @DateOfCreation        19 june 2018
    * @ShortDescription      Dashboard second version file
    * @return                Array
    */    
    public function dashboard(Request $request)
    { 
        $request_data =$this->securityLibObj->decryptInput($request);
        $request_data['date'] = Date('Y-m-d');
        $student_id = $request_data['student_id'];

        $user_id = Auth::id();
        //Get school id 
        $request_data['school_id'] = User::find($user_id)->school_id;

        //Check Absent Date
        $student_absent_id = 0;
        $student_absent_model = new StudentAbsent;
        $absent_id = $student_absent_model->check_absent_in_dashboard($request_data);
        if($absent_id){
            $student_absent_id = $absent_id->student_absent_id;
        }

        //Get Default dashboard
        $student_vehicle_routes_model = new StudentVehicleRoute;
        $result1 = $student_vehicle_routes_model->student_vehicle_routes($student_id);

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
        $now = Date('H:i');
        $pickUpbusFlag = 0;
        $dropbusFlag = 0;
       
        $pickup = (array) $result1;
        $drop = (array) $result1;
        //Get Stoppage Name
        $pickup['stoppage_name'] = Stoppage::find($pickup['pickup_stoppage'])->stoppage_name;
        $pickup['pickup_time'] = date("h:i A", strtotime($pickup['pickup_time']));
        $pickup['drop_time'] = date("h:i A", strtotime($pickup['drop_time']));

        $drop['stoppage_name'] = Stoppage::find($drop['drop_stoppage'])->stoppage_name;
        $drop['pickup_time'] = date("h:i A", strtotime($drop['pickup_time']));
        $drop['drop_time'] = date("h:i A", strtotime($drop['drop_time']));
        unset($pickup['drop_time'],$pickup['drop_stoppage']);
        $result['pickup'] = $pickup;
        $result['pickup']['student_absent_id'] = $student_absent_id;
        $result['pickup']['type'] = 'pickup';
        unset($drop['pickup_time'],$drop['pickup_stoppage']);
        $result['drop'] = $drop;
        $result['drop']['student_absent_id'] = $student_absent_id;
        $result['drop']['type'] = 'drop';

        unset($result['pickup']['vehicle_id'],$result['pickup']['route_id'],$result['drop']['vehicle_id'],$result['drop']['route_id']);
        

        //Bus start and end time
        $vehicle_routes_model = new VehicleRoute;
        $result2 = $vehicle_routes_model->vehicle_routes($pickup);
        $result2->start_time = date("h:i",strtotime($result2->start_time));
        $result2->end_time = date("h:i",strtotime($result2->end_time));
        $result['pickup']['school_id'] = $request_data['school_id'];
        $result['drop']['school_id'] = $request_data['school_id'];
        if($result2->start_time >= $now){
            $result = $this->dashboardRoute($result['pickup'],0);
        }else if($result2->end_time >= $now){
            $result = $this->dashboardRoute($result['drop'],0);
        }else{                
            $result = $this->dashboardRoute($result['pickup'],0);
        }

        return $this->resultResponse(
                            Config::get('restresponsecode.SUCCESS'), 
                            [$result], 
                            [],
                            trans('Parents::event.passenger_dashboard_successfull'), 
                            $this->http_codes['HTTP_OK']
                          );

        
    }
    /**
     * @DateOfCreation        27 July 2018
     * @ShortDescription      Get dashboardRoute 
     * @return                Array
     */
    protected function dashboardRoute($requestData, $type)
    {
        $user_id = Auth::id();
        $getVehicleRoute = array();

        $vehicle_route_id = $requestData['vehicle_route_id'];
        //$route_type = strtolower($requestData['route_type']);
        
        //Get Ids from vehicle route table
        $getVehicleId = VehicleRoute::select('vehicle_id', 'route_id', 'start_time', 'shift')
                            ->where([
                                ['vehicle_route_id', '=', $vehicle_route_id]
                            ])
                            ->first()->toArray();
        $getVehicleId['start_time'] = date('H:i',strtotime($getVehicleId['start_time']));
        $vehicle_id = $getVehicleId['vehicle_id'];
        $route_id = $getVehicleId['route_id'];
        $getVehicleRoute = $requestData;
        
        $getVehicleRoute['shift'] = $getVehicleId['shift'];

        //Driver and vehicle more information get
        $getVehicleRoute['vehicle'] = Vehicle::select('bus_capacity', 'bus_permit_validity', 'bus_insurance', 'emergency_contact_number', 'last_maintenance', 'bus_sefety_rating', 'gps_enabled', 'driver_police_verification', 'driver_rating')
                                        ->join('employee_vehicles', 'employee_vehicles.vehicle_id', '=', 'vehicles.vehicle_id')
                                        ->join('users', 'users.user_id', '=', 'employee_vehicles.user_driver_id')
                                        ->where('vehicles.vehicle_id',$vehicle_id)->first();
        $trip_model = new Trip;
        $trip_data = $trip_model->get_trip_delay_data($vehicle_route_id, $requestData['school_id']);
        if (empty($trip_data)) {
            $getVehicleRoute['vehicle']['delay'] = 0;
        } else {
            $getVehicleRoute['vehicle']['delay'] = $trip_data->delay;
        }

        $getVehicleRoute['vehicle']['vehicle_id'] = $vehicle_id;
        
        //Get vehicle route
        $getVehicleRoute['route_waypoints'] = RouteStoppages::select('stoppages.stoppage_name', 'stoppages.stoppage_latitude', 'stoppages.stoppage_longitude', 'stoppages.stoppage_id', 'route_stoppages.duration as schedule_time')
                                    ->join('stoppages', 'stoppages.stoppage_id', '=', 'route_stoppages.stoppage_id')
                                    ->where('route_stoppages.route_id', '=', $route_id)
                                    ->orderby('route_stoppages.stoppage_id','ASC')
                                    ->get()->toArray();
        $start_time = $getVehicleId['start_time'];
        $arrival_time = $start_time;
        $i = 1;
        foreach ($getVehicleRoute['route_waypoints'] as &$value) {
            if($i == 1)
            {
                $value['schedule_time'] = date("h:i A", strtotime(+$value['schedule_time'] ."minutes", strtotime($arrival_time)));                
                $i++;
            }
            else
            {
                $arrival_time = date("h:i A", strtotime(+$value['schedule_time'] ."minutes", strtotime($arrival_time)));
                $value['schedule_time'] = $arrival_time;
            }
        }

        //Get vehicle route polyline
        $get_polyline = Routes::select('polyline')
                        ->where('route_id', '=', $route_id)
                        ->first();
        
        $polyline_array = json_decode($get_polyline['polyline']);
        $polyline = array();
        foreach ($polyline_array as $values) {
            $object = new stdClass();
            $object->points = $values;
            $polyline[] = $object;
        }
        
        $getVehicleRoute['polyline'] = $polyline;
        //Get user location and photo 
           
            
        $lastRoute = end($getVehicleRoute['route_waypoints']);

        $dt = Carbon::now();
        $i = 0;
        $lastStoppage = count($getVehicleRoute['route_waypoints']);

        if(!empty($requestData['pickup_stoppage'])){
            $stoppage = $requestData['pickup_stoppage'];
        }else{
            $stoppage = $requestData['drop_stoppage'];
        }
        foreach ($getVehicleRoute['route_waypoints'] as &$value) {
            /*$schedule_time = Carbon::parse($value['schedule_time']);
            $value['schedule_time'] = $schedule_time->format('h:i A');
            */            
            if($i == 0){
                $value = array_merge($value, array('boarding'=>'source'));
            }else if($value['stoppage_id'] == $stoppage){
                $value = array_merge($value, array('boarding'=>'student'));
            }else if($i == ($lastStoppage-1)){
                $value = array_merge($value, array('boarding'=>'destination'));
            }else{
                $value = array_merge($value, array('boarding'=>'other'));
            }
            $i++;
        }
        return $getVehicleRoute;
    }

    /**
     * @DateOfCreation        27 July 2018
     * @ShortDescription      Get dashboardRoute 
     * @return                Array
     */
    protected function route_list(Request $request)
    {   
        $user_id = Auth::id();

        //Get school id 
        $school_id = User::find($user_id)->school_id;
        $request_data =$this->securityLibObj->decryptInput($request);
        $student_id = $request_data['student_id'];
        // Validate request
        $validator = Validator::make($request_data, [
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
        $student_vehicle_routes_model = new StudentVehicleRoute;
        $student_data = $student_vehicle_routes_model->student_vehicle_routes_plan($student_id, $school_id);
        $trip_model = new Trip;
        $trip_stoppage_model = new TripStoppage;
        $vehicle_route_schedule_model = new VehicleRouteSchedules;
        if ($student_data) {
            $data['student'] = (array) $student_data;
            foreach ($student_data as $value) {
                $current_time = strtotime(date('H:i'));
                $pickup_time = strtotime($value->pickup_time);
                $drop_time = strtotime($value->drop_time);
                if($pickup_time < $current_time){
                    $route_data['student']['name'] = $student_data[0]->name;
                    $route_data['student']['profile_picture'] = env('EDUCATION_APP_URL').'/api/media/student_picture/'.$value->student_picture;
                    $route_data['student']['vehicle_id'] = $student_data[0]->vehicle_id;
                    $route_data['student']['route_id'] = $student_data[0]->route_id;
                    $route_data['student']['stoppage_id'] = $student_data[0]->stoppage_pickup;
                    $route_data['student']['vehicle_route_id'] = $student_data[0]->vehicle_route_id;
                } else if($drop_time < $current_time) {
                    $route_data['student']['name'] = $student_data[1]->name;
                    $route_data['student']['profile_picture'] = env('EDUCATION_APP_URL').'/api/media/student_picture/'.$value->student_picture;
                    $route_data['student']['vehicle_id'] = $student_data[1]->vehicle_id;
                    $route_data['student']['route_id'] = $student_data[1]->route_id;
                    $route_data['student']['stoppage_id'] = $student_data[1]->stoppage_drop;
                    $route_data['student']['vehicle_route_id'] = $student_data[1]->vehicle_route_id;
                } else {
                    $route_data['student']['name'] = $student_data[0]->name;
                    $route_data['student']['profile_picture'] = env('EDUCATION_APP_URL').'/api/media/student_picture/'.$value->student_picture;
                    $route_data['student']['vehicle_id'] = $student_data[0]->vehicle_id;
                    $route_data['student']['route_id'] = $student_data[0]->route_id;
                    $route_data['student']['stoppage_id'] = "";
                    $route_data['student']['vehicle_route_id'] = $student_data[0]->vehicle_route_id;
                }
            }
            $route_details = $vehicle_route_schedule_model->vehicle_route_schedule_plan($route_data['student'], $school_id);
            $trip_data = $trip_model->get_trip_data($route_data['student']['vehicle_route_id'], $school_id);
            $trip = $trip_data->current_stoppage_id;
            if (!empty($trip)) {
                $route_data['current_stoppage_id'] = $trip_data->current_stoppage_id;
            } else {
                $route_data['current_stoppage_id'] = 0;
            }
            $route_data['vehicle'] = Vehicle::select('vehicle_name','registration_number','bus_capacity', 'bus_permit_validity', 'bus_insurance', 'emergency_contact_number', 'last_maintenance', 'bus_sefety_rating', 'gps_enabled', 'driver_police_verification', 'driver_rating')
                                        ->join('employee_vehicles', 'employee_vehicles.vehicle_id', '=', 'vehicles.vehicle_id')
                                        ->join('users', 'users.user_id', '=', 'employee_vehicles.user_driver_id')
                                        ->where('vehicles.vehicle_id',$route_data['student']['vehicle_route_id'])->first();
            $trip_data = $trip_model->get_trip_delay_data($route_data['student']['vehicle_route_id'], $school_id);
            if (empty($trip_data)) {
                $route_data['vehicle']['delay'] = 0;
            } else {
                $route_data['vehicle']['delay'] = $trip_data->delay;
            }
            if(empty($route_data['vehicle']['vehicle_name'])) {
                $route_data['vehicle']['vehicle_name'] = "";
            }
            if(empty($route_data['vehicle']['registration_number'])) {
                $route_data['vehicle']['registration_number'] = "";
            }
            if(empty($route_data['vehicle']['bus_capacity'])) {
                $route_data['vehicle']['bus_capacity'] = "";
            }
            if(empty($route_data['vehicle']['bus_permit_validity'])) {
                $route_data['vehicle']['bus_permit_validity'] = "";
            }
            if(empty($route_data['vehicle']['bus_insurance'])) {
                $route_data['vehicle']['bus_insurance'] = "";
            }
            if(empty($route_data['vehicle']['emergency_contact_number'])) {
                $route_data['vehicle']['emergency_contact_number'] = "";
            }
            if(empty($route_data['vehicle']['last_maintenance'])) {
                $route_data['vehicle']['last_maintenance'] = "";
            }
            if(empty($route_data['vehicle']['bus_sefety_rating'])) {
                $route_data['vehicle']['bus_sefety_rating'] = "";
            }
            if(empty($route_data['vehicle']['gps_enabled'])) {
                $route_data['vehicle']['gps_enabled'] = "";
            }
            if(empty($route_data['vehicle']['driver_police_verification'])) {
                $route_data['vehicle']['driver_police_verification'] = "";
            }
            if(empty($route_data['vehicle']['driver_rating'])) {
                $route_data['vehicle']['driver_rating'] = "";
            }

            $i = 0;

            $trip_passed_data = $trip_model->get_trip_passed_data($route_data['student']['vehicle_route_id'], $school_id);
            if (!empty($trip_passed_data)) {
                $trip_stoppage_passed_data = $trip_stoppage_model->get_trip_stoppage_passed_data($trip_passed_data->trip_id, $school_id);
            }
            $route_data['stoppages'] = array();
            if (!$route_details->isEmpty()) {
                foreach ($route_details as $key => $route) {
                    $route_data['stoppages'][$key]['stoppage_id'] = $route->stoppage_id;
                    $route_data['stoppages'][$key]['stoppage_name'] = $route->stoppage_name;
                    $route_data['stoppages'][$key]['stoppage_latitude'] = $route->stoppage_latitude;
                    $route_data['stoppages'][$key]['stoppage_longitude'] = $route->stoppage_longitude;
                    $route_data['stoppages'][$key]['schedule_time'] = date('H:i A',strtotime($route->schedule_time));
                    $route_data['stoppages'][$key]['is_stoppage_passed'] = 0;
                    if(!empty($trip_stoppage_passed_data)){
                        if($trip_stoppage_passed_data->stoppage_id == $route->stoppage_id) {
                           $route_data['stoppages'][$key]['is_stoppage_passed'] = 1;
                        }
                    }
                }
                $lastStoppage = count($route_data['stoppages']);
                foreach ($route_data['stoppages'] as &$value) {
                    if($i == 0){
                        $value = array_merge($value, array('boarding'=>'source'));
                    }else if($value['stoppage_id'] == $route_data['student']['stoppage_id']){
                        $value = array_merge($value, array('boarding'=>'student'));
                    }else if($i == ($lastStoppage-1)){
                        $value = array_merge($value, array('boarding'=>'destination'));
                    }else{
                        $value = array_merge($value, array('boarding'=>'other'));
                    }
                    $i++;
                }
            }
            unset($route_data['student']['vehicle_id']);
            unset($route_data['student']['stoppage_id']);
            unset($route_data['student']['route_id']);
            unset($route_data['student']['vehicle_route_id']);
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $route_data, 
                [],
                trans('Parents::parent.student_route_list'),
                $this->http_codes['HTTP_OK']
            );
        }else{
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [], 
                [],
                trans('Parents::parent.student_route_list_no_data'), 
                $this->http_codes['HTTP_OK']
            );
        }
    }    
}