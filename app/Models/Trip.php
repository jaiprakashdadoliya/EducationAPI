<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

class Trip extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'trip_id';
    protected $table = 'trips'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'start_time','end_time', 'vehicle_route_id', 'assistant_id', 'driver_id', 'created_by', 'updated_by', 'resource_type', 'user_agent', 'ip_address', 'is_deleted','created_at', 'updated_at', 'current_stoppage_id', 'school_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
    * @DateOfCreation        06 August 2018
    * @ShortDescription      Trip data.
    * @param 1               $vehicle_route_id number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_trip_data($vehicle_route_id, $school_id) {
    	return $query =  DB::table($this->table)
                            ->select('current_stoppage_id')
                            ->where('trips.is_deleted','=',0)
                            ->where('trips.vehicle_route_id','=',$vehicle_route_id)
                            ->where('trips.school_id','=',$vehicle_route_id)
                            ->first();
    }

    /**
    * @DateOfCreation        07 August 2018
    * @ShortDescription      Trip delay data.
    * @param 1               $vehicle_route_id number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_trip_delay_data($vehicle_route_id, $school_id) {
        $date = date('Y-m-d');
        return $query =  DB::table($this->table)
                            ->select('trip_id','delay')
                            ->where('trips.is_deleted','=',0)
                            ->where('trips.vehicle_route_id','=',$vehicle_route_id)
                            ->where('trips.school_id','=',$school_id)
                            ->where('trips.start_time','!=',null)
                            ->where('trips.end_time','=',null)
                            ->whereDate('trips.created_at','=',$date)
                            ->orderBy('trip_id', 'desc')
                            ->first();
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Trip passed data.
    * @param 1               $vehicle_route_id number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_trip_passed_data($vehicle_route_id, $school_id){
        $date = date('Y-m-d');
        return $query =  DB::table($this->table)
                            ->select('trip_id','current_stoppage_id')
                            ->where('trips.is_deleted','=',0)
                            ->where('trips.vehicle_route_id','=',$vehicle_route_id)
                            ->where('trips.school_id','=',$school_id)
                            ->where('trips.start_time','!=',null)
                            ->where('trips.end_time','!=',null)
                            ->whereDate('trips.created_at','=',$date)
                            ->orderBy('trip_id', 'desc')
                            ->first();
    }
}
