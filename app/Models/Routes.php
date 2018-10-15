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
class Routes extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'route_id';
    protected $table = 'routes'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

        'route_reference', 'route_name', 'school_id', 'polyline', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'

    ];

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Fetch route list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_route_list($request_data) {         
        $selectData  =  [ 'route_id', 'route_reference', 'route_name'];
        $whereData   =  [
                        'school_id'=> $request_data['school_id'],
                        'is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData);

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
    * @DateOfCreation        27 July 2018
    * @ShortDescription      Update student details.
    * @param                 $student_id number
    * @return                Boolean
    */
    public function update_polyline($route_id, $polyline_array) {
        $isInserted = DB::table($this->table)
                        ->where('route_id', $route_id)
                        ->where('is_deleted', 0)
                        ->update($polyline_array);
                            
        if(!empty($isInserted)) {
            return true;
        }
        return false;
    }

    /**
    * @DateOfCreation        30 July 2018
    * @ShortDescription      Get route.
    * @param 1               $student_id number
    * @param 2               $school_id  number
    * @return                Array of object
    */
    public function get_route_details_by_route_reference_id($route_reference_id, $school_id) {
        return  DB::table('routes')
                ->where('route_reference', '=', $route_reference_id)
                ->where('school_id', '=', $school_id)
                ->where('is_deleted', '=', 0)
                ->first();
    }

    /**
    * @DateOfCreation        30 July 2018
    * @ShortDescription      Insert route data.
    * @param 1               $insert_data array
    * @return                Boolean
    */
    public function insert_data($insert_data) {
        $route_id = DB::table('routes')->insert($insert_data);
        return $route_id = DB::getPdo()->lastInsertId();
    }

    public function update_route($route_id, $route_array) {
        DB::table('routes')
            ->where('route_id', $route_id)
            ->where('is_deleted','=', 0)
            ->update($route_array);
        return true;
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Get route data.
    * @param 1               $school_id number
    * @param 1               $route_name string
    * @return                Array of object
    */
    public function get_route_data($route_name, $school_id) {
        return DB::table('routes')
                 ->select('route_id')
                 ->where('route_name', 'ILIKE', $route_name)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }

}