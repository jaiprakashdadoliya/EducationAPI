<?php
namespace App\Modules\Admin\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Config;
use DB;
use stdClass;
use Carbon\Carbon;
use App\Libraries\SecurityLib;
use App\Libraries\MapLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Models\Routes;
use App\Models\RouteStoppages;
use App\Models\VehicleRoute;
use App\Models\VehicleRouteSchedules;

/**
 * MapController Class
 *
 * @package                Education
 * @subpackage             MapController
 * @category               Controller
 * @DateOfCreation         24 July 2018
 * @ShortDescription       This controller perform all Map related functionality for admin api
 */

class MapController extends Controller
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

        // Init security library object
        $this->map_lib_obj = new MapLib();  
    }

   /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Direaction Route list save
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function route_path_save(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->getRequestData($request);
        
        $vehicle_route_data = $request_data[0];
        $stoppage_data = $request_data[1];
        
        $vehicle_route_data['school_id'] = User::find($user_id)->school_id;

        $route_stoppage_model = new RouteStoppages;
        $stoppage_lat_long  = $route_stoppage_model->get_stoppage_lat_long($vehicle_route_data);

        $last_lat_long = COUNT($stoppage_lat_long) - 1;

        $source = $stoppage_lat_long[0];
        $destination = $stoppage_lat_long[$last_lat_long];
        
        //Removed Source and destination        
        unset($stoppage_lat_long[0],$stoppage_lat_long[$last_lat_long]);

        $waypoints = array();
        //Stoppages
        foreach ($stoppage_lat_long as $value) {
            $waypoints[] = $value;
        }
        //Get PolyLine
        $data = $this->map_lib_obj->getPolylenForDirection($source, $destination, $waypoints);

        // Route update

        $polyline_array['polyline']   = json_encode($data);
        $polyline_array['updated_by'] = $user_id;
        $polyline_array['updated_at'] = date('Y-m-d H:i:s');
        
        //Update route table
        $route_update_model = new Routes;
        $update = $route_update_model->update_polyline($vehicle_route_data['route_id'], $polyline_array);

        $user_agent = $request->server('HTTP_USER_AGENT');

        //Insert into VehicleRouteSchedules table
        $vehicle_route_schedule_table = new VehicleRouteSchedules;
        try{
            DB::beginTransaction();
            $request_default['is_deleted'] = 1;
            $request_default['updated_by'] = $user_id;
            $request_default['updated_at'] = date('Y-m-d H:i:s');
            $request_default['vehicle_route_id'] = $vehicle_route_data['vehicle_route_id'];
            $request_default['school_id'] = $vehicle_route_data['school_id'];
            $delete = $vehicle_route_schedule_table->delete_data($request_default);

           
                foreach ($stoppage_data as $value) {
                    $value['schedule_time'] = $value['duration'];
                    $value['route_id'] = $vehicle_route_data['route_id'];
                    $value['vehicle_route_id'] = $vehicle_route_data['vehicle_route_id'];
                    $value['school_id'] = $vehicle_route_data['school_id'];

                    //Get vehicle_id
                    $value['vehicle_id'] = VehicleRoute::find($vehicle_route_data['vehicle_route_id'])->vehicle_id;
                    //Get stoppage_id
                    $value['stoppage_id'] = $route_stoppage_model->find($value['route_stoppage_id'])->stoppage_id;
                    
                    // Insert default
                    $value['created_at'] = date('Y-m-d H:i:s');    
                    $value['created_by'] = $user_id;
                    $value['resource_type'] = 'web';
                    $value['user_agent'] = $user_agent;
                    $value['ip_address'] = $request->ip();

                    unset($value['route_stoppage_id'],$value['stoppage_name'],$value['duration']);
                    $insert = $vehicle_route_schedule_table->insert_data($value);
                }

                // validate, is query executed successfully
                if ($insert) {
                    DB::commit();
                    return $this->resultResponse(
                        Config::get('restresponsecode.SUCCESS'),
                        [],
                        [],
                        trans('Admin::messages.success'),
                        $this->http_codes['HTTP_OK']
                    );
                } else {
                    DB::rollback();
                    return $this->resultResponse(
                        Config::get('restresponsecode.ERROR'),
                        [],
                        [],
                        trans('Admin::messages.error'),
                        $this->http_codes['HTTP_OK']
                    );
                }
                   
        }catch(\Exception $e) {
            return $e->getMessage();
        }

    }

   /**
    * @DateOfCreation        02 Aug 2018
    * @ShortDescription      Route draw in map
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function route_path_draw(Request $request)
    {
        $user_id = Auth::id();
        $time = date('h:i');
        $request_data = $this->getRequestData($request);
        $request_data['school_id'] = User::find($user_id)->school_id;
        $result = array();

        $vehicle_route_model = new VehicleRoute;
        $get_route_id  =  $vehicle_route_model
                            ->select('route_id', 'vehicle_route_id')
                            ->where([
                                ['vehicle_id', $request_data['vehicle_id']],
                                ['school_id', $request_data['school_id']],
                                ])
                            ->where('start_time', '>', $time)
                            ->where('end_time', '<', $time)
                            ->first();
        if($get_route_id){
            $request_data['route_id'] = $get_route_id->route_id;
            $result['vehicle_route_id'] = $get_route_id->vehicle_route_id;
        }else{
            $get_route_id = $vehicle_route_model
                              ->select('route_id', 'vehicle_routes.vehicle_route_id')
                              ->join('trips', 'trips.vehicle_route_id', '=', 'vehicle_routes.vehicle_route_id')
                              ->where([
                                  ['vehicle_id', $request_data['vehicle_id']],
                                  ['vehicle_routes.school_id', $request_data['school_id']],
                                ])
                              ->orderby('trip_id', 'DESC')
                              ->first();

            if($get_route_id){
                $request_data['route_id'] = $get_route_id->route_id;
                $result['vehicle_route_id'] = $get_route_id->vehicle_route_id;
            }else{
                $get_route_id  =  $vehicle_route_model
                            ->select('route_id', 'vehicle_route_id')
                            ->where([
                                ['vehicle_id', $request_data['vehicle_id']],
                                ['school_id', $request_data['school_id']],
                                ])
                            ->orderby('vehicle_route_id', 'DESC')
                            ->first();
                $request_data['route_id'] = $get_route_id->route_id;
                $result['vehicle_route_id'] = $get_route_id->vehicle_route_id;
            }
        }
        

        $route_stoppage_model = new RouteStoppages;
        $stoppage_lat_long  = $route_stoppage_model->get_route_path_lat_long($request_data);
        if($stoppage_lat_long){                    
            $last_lat_long = COUNT($stoppage_lat_long) - 1;           
            $waypoints = array();
            //Stoppages
            foreach ($stoppage_lat_long as $value) {
                $value = (array) $value;
                $waypoints['position']['lat'] = floatval($value['lat']);
                $waypoints['position']['lng'] = floatval($value['lng']);
                $waypoints['title'] = $value['stoppage_name'];
                $points[] = $waypoints;
            }
            $result['waypoints'] = $points;

            //Get vehicle route polyline
            $get_polyline = Routes::select('polyline')
                            ->where('route_id', '=', $request_data['route_id'])
                            ->first();
            
            $polyline_array = json_decode($get_polyline['polyline']);
            $polyline = array();
            foreach ($polyline_array as $string) {
                $polylinelist = $this->map_lib_obj->getLatLongbyPolylen($string);
                foreach ($polylinelist as $value) {
                    $object = new stdClass();
                    $object->lat = $value[0];
                    $object->lng = $value[1];
                    $polyline[] = $object;
                }
            }
            $result['polyline'] = $polyline;
            if ($result) {
                return $this->resultResponse(
                    Config::get('restresponsecode.SUCCESS'),
                    $result,
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
        }else{
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('Admin::messages.error'),
                    $this->http_codes['HTTP_OK']
                );
        }
    }

}
