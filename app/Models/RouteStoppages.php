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
class RouteStoppages extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'route_stoppage_id';
    protected $table = 'route_stoppages'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'route_id', 'stoppage_id', 'school_id', 'duration', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Fetch route list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_route_stoppages($request_data) {   
        $selectData  =  [ 'route_stoppages.route_stoppage_id', 'stoppages.stoppage_name', 'route_stoppages.duration'];
        $whereData   =  [
                        'route_stoppages.school_id'=> $request_data['school_id'],
                        'route_stoppages.is_deleted'=>  0
                        ];
        if(!empty($request_data['route_id']))
        {
            $whereData['route_stoppages.route_id'] = $request_data['route_id'];
        }
        return $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->join("stoppages", 'route_stoppages.stoppage_id', '=', 'stoppages.stoppage_id')
                    ->orderby("route_stoppage_id", "ASC")
                    ->get();
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Fetch stoppage list of vehicle route.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_stoppage_list($request_data) {         
        $selectData  =  ['route_stoppage_id', 'stoppage_name', 'duration'];
        $whereData   =  [
                        'route_stoppages.school_id'=> $request_data['school_id'],
                        'route_stoppages.is_deleted'=>  0
                        ];
        if(!empty($request_data['route_id']))
        {
            $whereData['route_stoppages.route_id'] = $request_data['route_id'];
        }
        return $query =  DB::table($this->table)
                        ->select($selectData)
                        ->where($whereData)
                        ->join('stoppages', 'route_stoppages.stoppage_id', '=', 'stoppages.stoppage_id')
                        ->get();
    }

    /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Fetch stoppage lat and long list.
    * @param 1               $route_id number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_stoppage_lat_long($request_data) {         
        $selectData  =  [ DB::raw("CONCAT(stoppage_latitude, ',', stoppage_longitude)")];
        $whereData   =  [
                        'route_stoppages.route_id'=> $request_data['route_id'],
                        'route_stoppages.school_id'=> $request_data['school_id'],
                        'route_stoppages.is_deleted'=>  0
                        ];
        
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->join("stoppages", 'route_stoppages.stoppage_id', '=', 'stoppages.stoppage_id')
                    ->get()
                    ->map(function ($list) {
                        return $list->concat;
                    });
        return $query;
    }

    /**
    * @DateOfCreation        30 July 2018
    * @ShortDescription      Insert route stoppage data.
    * @param 1               $insert_data array
    * @return                Boolean
    */
    public function insert_data($insert_data) {
        DB::table('route_stoppages')->insert($insert_data);
        return true;
    }

    /**
    * @DateOfCreation        30 July 2018
    * @ShortDescription      Update route stoppage details.
    * @param                 $route_id number
    * @return                Boolean
    */
    public function update_route_stoppage($route_id, $stoppage_id, $school_id, $route_update) {
        return DB::table('route_stoppages')
                    ->where('route_id', $route_id)
                    ->where('stoppage_id', $stoppage_id)
                    ->where('school_id', $school_id)
                    ->where('is_deleted','=',0)
                    ->update($route_update);
    }

    /**
    * @DateOfCreation        3 August 2018
    * @ShortDescription      Get route stoppage details.
    * @param 1               $route_reference number
    * @param 2               $stoppage_reference number
    * @param 2               $school_id number
    * @return                Boolean
    */
    public function get_route_stoppages_details($route_id, $stoppage_id, $school_id) {
        return $query =  DB::table($this->table)
                    ->select('route_stoppage_id')
                    ->where('is_deleted','=',0)
                    ->where('route_id','=',$route_id)
                    ->where('stoppage_id','=',$stoppage_id)
                    ->where('school_id','=',$school_id)
                    ->get();
    }

    /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Fetch stoppage lat and long list.
    * @param 1               $route_id number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_route_path_lat_long($request_data) {         
        $selectData  =  [ 'stoppage_name', 'stoppage_latitude as lat', 'stoppage_longitude as lng'];
        $whereData   =  [
                        'route_stoppages.route_id'=> $request_data['route_id'],
                        'route_stoppages.school_id'=> $request_data['school_id'],
                        'route_stoppages.is_deleted'=>  0
                        ];
        
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->join("stoppages", 'route_stoppages.stoppage_id', '=', 'stoppages.stoppage_id')
                    ->get();
        return $query;
    }
}

