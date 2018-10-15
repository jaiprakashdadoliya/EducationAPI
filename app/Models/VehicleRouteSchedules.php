<?php
namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * VehicleRouteSchedules
 *
 * @subpackage             VehicleRouteSchedules
 * @category               Model
 * @author                 chetan  <wagh.chetan@fxbytes.com>
 * @DateOfCreation         25 July 2018
 * @ShortDescription       This model connect with the student vehicle routes table 
 */

class VehicleRouteSchedules extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'vehicle_route_schedule_id';
    protected $table = 'vehicle_route_schedules'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'vehicle_route_id', 'vehicle_id', 'stoppage_id', 'route_id', 'school_id', 'schedule_time', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'

    ];

    /**
    * @DateOfCreation        06 August 2018
    * @ShortDescription      vehicle route schedule plane.
    * @param 1               $school_id number                
    * @return                Array of object
    */

    public function vehicle_route_schedule_plan($data, $school_id){
        return $query =  DB::table($this->table)
                    ->select('stoppages.stoppage_id','stoppages.stoppage_name','stoppages.stoppage_latitude','stoppages.stoppage_longitude','vehicle_route_schedules.schedule_time')
                    ->join('vehicles', 'vehicles.vehicle_id', '=', 'vehicle_route_schedules.vehicle_id')
                    ->join('routes', 'routes.route_id', '=', 'vehicle_route_schedules.route_id')
                    ->join('stoppages', 'stoppages.stoppage_id', '=', 'vehicle_route_schedules.stoppage_id')
                    ->where('vehicle_route_schedules.vehicle_id','=',$data['vehicle_id'])
                    ->where('vehicle_route_schedules.route_id','=',$data['route_id'])
                    ->where('vehicle_route_schedules.school_id','=',$school_id)
                    ->where('vehicle_route_schedules.is_deleted','=',0)
                    ->where('vehicles.is_deleted','=',0)
                    ->where('routes.is_deleted','=',0)
                    ->where('stoppages.is_deleted','=',0)
                    ->get();
    }


    /**
    * @DateOfCreation        07 Aug 2018
    * @ShortDescription      Delete vehicle route schedules data.
    * @param 1               $insert_data array
    * @return                Boolean
    */
    public function delete_data($delete_data) {
        $delete = DB::table($this->table)
                        ->where('vehicle_route_id', $delete_data['vehicle_route_id'])
                        ->where('school_id', $delete_data['school_id'])
                        ->update($delete_data);
        if($delete){
            return true;
        }else{
            return false;
        }
    }

    /**
    * @DateOfCreation        07 Aug 2018
    * @ShortDescription      Insert vehicle route schedules data.
    * @param 1               $insert_data array
    * @return                Boolean
    */
    public function insert_data($insert_data) {
        $insert = DB::table($this->table)->insert($insert_data);
        if($insert){
            return true;
        }else{
            return false;
        }
    }
}
