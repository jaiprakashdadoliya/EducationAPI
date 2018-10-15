<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * Stoppage
 *
 * @subpackage             Stoppage
 * @category               Model
 * @author                 chetan  <wagh.chetan@fxbytes.com>
 * @DateOfCreation         25 July 2018
 * @ShortDescription       This model connect with the Schools table 
 */

class Stoppage extends Model
{

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'stoppage_id';
    protected $table = 'stoppages'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'stoppage_name', 'stoppage_address', 'stoppage_latitude', 'stoppage_longitude', 'location_type', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Get stoppage list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_stoppage($requestData) {
        $selectData  =  ['stoppage_id','stoppage_reference','stoppage_name','stoppage_address','location_type'];
        $whereData   =  [
                        'school_id'=> $requestData['school_id'],
                        'is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData);
        /* Condition for Filtering the result */
        if(!empty($requestData['filtered'])){
            foreach ($requestData['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(stoppage_name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(stoppage_address AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(location_type AS TEXT)'), 'like', '%'.$value['value'].'%');
                            });
            }
        }
        /* Condition for Sorting the result */
        if(!empty($requestData['sorted'])){
            foreach ($requestData['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $query = $query->orderBy($value['id'], $orderBy);
            }
        }
        if($requestData['page'] > 0){
            $offset = $requestData['page']*$requestData['pageSize'];
        }else{
            $offset = 0;
        }
        
        $Data['pages'] = ceil($query->count()/$requestData['pageSize']);
        $Data['data'] = $query
                    ->offset($offset)
                    ->limit($requestData['pageSize'])
                    ->get()
                    ->map(function ($list) {
                        return $list;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        30 July 2018
    * @ShortDescription      Get stoppage id.
    * @param 1               $stoppage_reference number                
    * @return                Array of object
    */
    public function get_stoppage_id_by_reference_id($stoppage_reference, $school_id) {
        return DB::table('stoppages')
                ->where('stoppage_reference', '=', $stoppage_reference)
                ->where('school_id', '=', $school_id)
                ->where('is_deleted', '=', 0)
                ->first();
    }
}
