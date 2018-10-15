<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * StudentVehicleRoute
 *
 * @subpackage             StudentVehicleRoute
 * @category               Model
 * @author                 chetan  <wagh.chetan@fxbytes.com>
 * @DateOfCreation         25 July 2018
 * @ShortDescription       This model connect with the student vehicle routes table 
 */
class StudentVehicleRoute extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'student_vehicle_route_id';
    protected $table = 'student_vehicle_routes'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_id', 'vehicle_id', 'route_id', 'route_type', 'school_id', 'stoppage_pickup', 'pickup_time', 'drop_time', 'stoppage_drop', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Use for dashboard API.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function student_vehicle_routes($student_id) {         
        $res = DB::table($this->table)
                    ->select('vehicle_routes.vehicle_route_id','student_vehicle_routes.vehicle_id','student_vehicle_routes.route_id','pickup_time','stoppage_pickup as pickup_stoppage','drop_time','stoppage_drop as drop_stoppage','users.name','users.mobile','vehicle_name','registration_number')
                    ->join('vehicles', 'vehicles.vehicle_id', '=', 'student_vehicle_routes.vehicle_id')
                    ->join('vehicle_routes', function($join)
                       {
                          $join->on('vehicle_routes.vehicle_id', '=', 'student_vehicle_routes.vehicle_id')
                          ->on('vehicle_routes.route_id', '=', 'student_vehicle_routes.route_id');
                       })
                    ->join('employee_vehicles', 'employee_vehicles.vehicle_id', '=', 'vehicles.vehicle_id')
                    ->join('users', 'users.user_id', '=', 'employee_vehicles.user_driver_id')
                    ->where('student_id', '=', $student_id)
                    ->first();
        return $res;
    }


    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Use for dashboard API.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_student_allocation($request_data) {         
        $selectData  =  [ DB::raw("string_agg(students.name, ',') as name"),
                          DB::raw("(SELECT stoppage_name FROM stoppages WHERE stoppages.stoppage_id = student_vehicle_routes.stoppage_pickup) as stoppage_pickup"),
                          'student_vehicle_routes.pickup_time',
                          DB::raw("(SELECT stoppage_name FROM stoppages WHERE stoppages.stoppage_id = student_vehicle_routes.stoppage_drop) as stoppage_drop"),
                          'student_vehicle_routes.drop_time'];
        $whereData   =  [
                        'student_vehicle_routes.school_id'=> $request_data['school_id'],
                        'student_vehicle_routes.is_deleted'=>  0
                        ];
        if(!empty($request_data['vehicle_id']))
        {
            $whereData['student_vehicle_routes.vehicle_id'] = $request_data['vehicle_id'];
        }

        $query =  DB::table($this->table)
                        ->select($selectData)
                        ->where($whereData)
                        ->join('students', 'student_vehicle_routes.student_id', '=', 'students.student_id')
                        ->groupBy('student_vehicle_routes.stoppage_pickup', 'student_vehicle_routes.pickup_time', 'student_vehicle_routes.drop_time', 'student_vehicle_routes.stoppage_drop');
                    

        /* Condition for Filtering the result */
        if(!empty($request_data['filtered'])){
            foreach ($request_data['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(students.student_reference AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(students.name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(students.class AS TEXT)'), 'like', '%'.$value['value'].'%');
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
                    ->map(function ($list) {
                        $list->pickup_time = date("g:i A", strtotime($list->pickup_time));
                        $list->drop_time = date("g:i A", strtotime($list->drop_time));
                        return $list;
                    });
        return $Data;
    }

    /**

    * @DateOfCreation        03 August 2018
    * @ShortDescription      Student vehicle route plane.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function student_vehicle_routes_plan($student_id, $school_id){
        return $query =  DB::table($this->table)
                            ->select('students.name', 'students.student_picture', 'vehicle_id', 'route_id', 'pickup_time', 'stoppage_pickup', 'drop_time', 'stoppage_drop','route_type', 'vehicle_route_id')
                            ->join('students', 'students.student_id', '=', 'student_vehicle_routes.student_id')
                            ->where('student_vehicle_routes.student_id','=',$student_id)
                            ->where('student_vehicle_routes.school_id','=',$school_id)
                            ->where('student_vehicle_routes.is_deleted','=',0)
                            ->where('students.is_deleted','=',0)
                            ->get();
    }                        
    /**
    * @DateOfCreation        07 August 2018
    * @ShortDescription      Check student routes exists by student id in table.
    * @param 1               Object $request this contains full request.
    * @return                Array of object
    */
    public function check_student_route_allocation($request_data) {
        $query = DB::table("student_vehicle_routes")
                    ->select("student_vehicle_route_id", "student_id")
                    ->where([
                        "school_id" => $request_data['school_id'],
                        "is_deleted" => 0,
                        "student_id" => $request_data['student_id']
                    ])
                    ->first();
        return $query;
    }

    /**
    * @DateOfCreation        07 August 2018
    * @ShortDescription      Insert or update vehicle route for student.
    * @param 1               $mode 1 for insert, 2 for update
    * @param 2               array of where conditions
    * @param 3               Object $request this contains full request.
    * @return                Boolean value
    */
    public function add_vehicle_route($request_data, $where = array(), $mode = 1) {
        if($mode == 1){
            DB::table("student_vehicle_routes")
                ->insert($request_data);
        }else{
            DB::table("student_vehicle_routes")
                ->where($where)
                ->update($request_data);
        }
        return true;

}
  

}