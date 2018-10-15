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
class beacons extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'beacon_id';
    protected $table = 'beacons'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'major', 'miner', 'school_id', 'uuid', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'

    ];

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Fetch Beacons list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_beacon_list($request_data) {
        $select_data  =  [ "students.beacon_name", "beacons.uuid", "beacons.major", "beacons.miner", "students.student_id"];
        $where_data   =  [
                            "beacons.school_id" => $request_data['school_id'],
                            "beacons.is_deleted" => 0,
                            "students.school_id" => $request_data['school_id'],
                            "students.is_deleted" => 0
                        ];
        $query =  DB::table("beacons")
                      ->select($select_data)
                      ->where($where_data)
                      ->join("students", "beacons.beacon_id", "=", "students.beacon_id");

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
}