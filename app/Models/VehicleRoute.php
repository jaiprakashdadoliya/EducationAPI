<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * VehicleRoute
 *
 * @subpackage             VehicleRoute
 * @category               Model
 * @author                 chetan  <wagh.chetan@fxbytes.com>
 * @DateOfCreation         25 July 2018
 * @ShortDescription       This model connect with the vehicle routes table 
 */
class VehicleRoute extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'vehicle_route_id';
    protected $table = 'vehicle_routes'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vehicle_id', 'route_id', 'school_id', 'start_time', 'end_time', 'shift', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];


    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Use for dashboard API.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function vehicle_routes($request) {         
        $res = DB::table($this->table)
                    ->select('start_time', 'end_time')
                    ->where('route_id', '=', $request['route_id'])
                    ->where('vehicle_id', '=', $request['vehicle_id'])
                    ->first();
        return $res;
    }
    
    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Fetch list of alloted routes.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_route_allocation($request_data) {         
        $selectData  =  [ 
                            'vehicle_route_id', 'routes.route_name', 'vehicles.registration_number', 'start_time', 'end_time','vehicle_routes.route_id', 'shift'
                        ];
        $whereData   =  [
                        'vehicle_routes.school_id'=> $request_data['school_id'],
                        'vehicle_routes.is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                        ->select($selectData)
                        ->where($whereData)
                        ->join('vehicles', 'vehicle_routes.vehicle_id', '=', 'vehicles.vehicle_id')
                        ->join('routes', 'vehicle_routes.route_id', '=', 'routes.route_id') ;

        /* Condition for Filtering the result */
        if(!empty($request_data['filtered'])){
            foreach ($request_data['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(routes.route_name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(vehicles.registration_number AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(vehicle_routes.start_time AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(vehicle_routes.end_time AS TEXT)'), 'like', '%'.$value['value'].'%');
                            });
            }
        }

        /* Condition for Sorting the result */
        if(!empty($request_data['sorted'])){
            foreach ($request_data['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $query = $query->orderBy($value['id'], $orderBy);
            }
        }
        if($request_data['page'] > 0){
            $offset = $request_data['page']*$request_data['pageSize'];
        }else{
            $offset = 0;
        }
        
        $Data['pages'] = ceil($query->count()/$request_data['pageSize']);
        $Data['data'] = $query
                    ->offset($offset)
                    ->limit($request_data['pageSize'])
                    ->get()
                    ->map(function ($parentList) {
                        return $parentList;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        07 august 2018
    * @ShortDescription      Fetch vehicle id, route id and vehicle_route_id through their references.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_vehicle_route_id_for_student($request_data) {         
        $select_data  =  [
                            "vehicles.vehicle_id",
                            "routes.route_id",
                            "vehicle_routes.vehicle_route_id"
                        ];
        $where_data   =  [
                            "vehicle_routes.school_id" => $request_data['school_id'],
                            "vehicle_routes.is_deleted" => 0,
                            "vehicles.vehicle_reference" => $request_data['vehicle_reference'],
                            "vehicles.is_deleted" => 0,
                            "vehicles.school_id" => $request_data['school_id'],
                            "routes.route_reference" => $request_data['route_reference'],
                            "routes.is_deleted" => 0,
                            "routes.school_id" => $request_data['school_id']
                        ];
        $query =  DB::table("vehicle_routes")
                    ->select($select_data)
                    ->where($where_data)
                    ->join("vehicles", "vehicle_routes.vehicle_id", "=", "vehicles.vehicle_id")
                    ->join("routes", "vehicle_routes.route_id", "=", "routes.route_id")
                    ->first();
        return $query;
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Fetch list of scheduled routes.
    * @param 1               $request_data array of requested parameters
    * @return                Array of object
    */
    public function get_scheduled_route_list($request_data) {         
        $select_data  =  [ 
                            'vehicles.vehicle_name',
                            'routes.route_name',
                            'routes.route_id',
                            'vehicle_routes.shift',
                            DB::raw("(SELECT user_reference FROM users WHERE users.user_id = employee_vehicles.user_driver_id) as driver"),
                            DB::raw("(SELECT user_reference FROM users WHERE users.user_id = employee_vehicles.user_assistant_id) as assistant"),
                            "devices.device_reference",
                            'vehicle_routes.start_time',
                            'vehicle_routes.end_time',
                            'vehicle_routes.vehicle_route_id',
                        ];
        $where_data   =  [
                            'vehicle_routes.school_id'=> $request_data['school_id'],
                            'vehicle_routes.is_deleted'=>  0,
                            'vehicles.school_id' => $request_data['school_id'],
                            'vehicles.is_deleted' => 0,
                            'routes.school_id' => $request_data['school_id'],
                            'routes.is_deleted' => 0,
                            'employee_vehicles.school_id' => $request_data['school_id'],
                            'employee_vehicles.is_deleted' => 0
                        ];
        $query = DB::table($this->table)
                    ->select($select_data)
                    ->where($where_data)
                    ->join('vehicles', 'vehicle_routes.vehicle_id', '=', 'vehicles.vehicle_id')
                    ->join('routes', 'vehicle_routes.route_id', '=', 'routes.route_id')
                    ->join('employee_vehicles', 'vehicle_routes.vehicle_id' , "=", "employee_vehicles.vehicle_id")
                    ->join('device_vehicles', 'vehicle_routes.vehicle_id' , "=", "device_vehicles.vehicle_id")
                    ->join('devices', 'device_vehicles.device_id' , "=", "devices.device_id");

        /* Condition for Filtering the result */
        if(!empty($request_data['filtered'])){
            foreach ($request_data['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(routes.route_name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(vehicle_routes.shift AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(devices.device_reference AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(vehicle_routes.start_time AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(vehicle_routes.end_time AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(driver AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(assistant AS TEXT)'), 'like', '%'.$value['value'].'%');
                            });
            }
        }
        $query = $query->groupby("vehicles.vehicle_name", "routes.route_name", "routes.route_id", "vehicle_routes.shift", "driver", "assistant", "devices.device_reference", "vehicle_routes.start_time", "vehicle_routes.end_time", "vehicle_routes.vehicle_route_id");

        /* Condition for Sorting the result */
        if(!empty($request_data['sorted'])){
            foreach ($request_data['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $query = $query->orderBy($value['id'], $orderBy);
            }
        }
        if($request_data['page'] > 0){
            $offset = $request_data['page']*$request_data['pageSize'];
        }else{
            $offset = 0;
        }

        $Data['pages'] = ceil(count($query->get())/$request_data['pageSize']);      
        
        $Data['data'] = $query
                    ->offset($offset)
                    ->limit($request_data['pageSize'])
                    ->get()
                    ->map(function ($parentList) {
                        return $parentList;
                    });
        return $Data;

    }
   /**
    * @DateOfCreation        08 august 2018
    * @ShortDescription      Get vehicle route data by route id, vehicle_id, school_id.
    * @param 1               $school_id number                
    * @param 2               $route_id number                
    * @param 3               $shift string                
    * @param 4               $vehicle_id number                
    * @return                Array of object
    */
    public function get_vehicle_route_data($vehicle_id, $route_id, $shift, $school_id) {
        return DB::table('vehicle_routes')
                 ->select('vehicle_route_id')
                 ->where('vehicle_id', '=', $vehicle_id)
                 ->where('route_id', '=', $route_id)
                 ->where('shift', '=', $shift)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }

    /**
    * @DateOfCreation        08 august 2018
    * @ShortDescription      Update route data.
    * @param 1               $school_id number                
    * @param 2               $vehicle_route_array array
    * @param 3               $vehicle_route_id number
    * @return                Array of object
    */
    public function update_vehicle_route($vehicle_route_array, $vehicle_route_id, $school_id) {
        return DB::table('vehicle_routes')
                 ->where('vehicle_route_id', $vehicle_route_id)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->update($vehicle_route_array);

    }

    /**
    * @DateOfCreation        08 august 2018
    * @ShortDescription      Insert vehicle route data.
    * @param 1               $insert_data array
    * @return                Array of object
    */
    public function insert_vehicle_route($insert_data){
       DB::table($this->table)->insert($insert_data);
    }
}