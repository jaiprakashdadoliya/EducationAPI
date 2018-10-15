<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * EmployeeVehicle
 *
 * @subpackage             EmployeeVehicle
 * @category               Model
 * @author                 toshik  <parihar.toshik@fxbytes.com>
 * @DateOfCreation         26 July 2018
 * @ShortDescription       This model connect with the student vehicle routes table 
 */
class EmployeeVehicle extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'employee_vehicle_id';
    protected $table = 'employee_vehicles'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vehicle_id', 'user_driver_id', 'user_assistant_id', 'school_id', 'effective_date', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Use for dashboard API.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_staff_allocation($request_data) {         
        $res = DB::table($this->table)
                    ->select();
        $selectData  =  [
                            'employee_vehicle_id',
                            'vehicles.registration_number',
                            'employee_vehicles.effective_date',
                            DB::raw("(SELECT name FROM users WHERE users.user_id = employee_vehicles.user_driver_id) as driver"),
                            DB::raw("(SELECT name FROM users WHERE users.user_id = employee_vehicles.user_assistant_id) as assistant")
                        ];
        $whereData   =  [
                        'employee_vehicles.school_id'=> $request_data['school_id'],
                        'employee_vehicles.is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                        ->select($selectData)
                        ->where($whereData)
                        ->join('vehicles', 'employee_vehicles.vehicle_id', '=', 'vehicles.vehicle_id');
                    

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
                    ->map(function ($parentList) {
                        return $parentList;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Update employee vehicle.
    * @param 1               $school_id number                
    * @param 2               $vehicle_id number 
    * @param 3               $employee_vehicle_array
    * @return                Array of object
    */
    public function update_employee_vehicle_csv_data($employee_vehicle_array, $vehicle_id, $school_id) {
        return DB::table('employee_vehicles')
                 ->where('vehicle_id', '=', $vehicle_id)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->update($employee_vehicle_array);
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Get employee vehicle data.
    * @param 1               $school_id number
    * @param 2               $employee_vehicle_id number
    * @return                Array of object
    */
    public function get_vehicle_data_by_vehicle_id($vehicle_id, $school_id) {
        return DB::table('employee_vehicles')
                 ->where('vehicle_id', '=', $vehicle_id)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }

    /**
    * @DateOfCreation        08 august 2018
    * @ShortDescription      Insert employee vehicle data.
    * @param 1               $insert_data array
    * @return                Array of object
    */
    public function insert_employee_vehicle($insert_data){
        DB::table($this->table)->insert($insert_data);
    }
}
