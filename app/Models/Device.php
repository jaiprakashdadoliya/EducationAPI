<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;
/**
 * Device
 *
 * @subpackage             Device
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         17 July 2018
 * @ShortDescription       This model connect with the Device table 
 */
class Device extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'device_id';
    protected $table = 'devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'device_name', 'device_token', 'device_type', 'os_version', 'device_model', 'device_reference', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted',
        'user_id', 'school_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Get device list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_device($requestData) {
        $selectData  =  ['device_id','device_reference','device_name'];
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
                                ->orWhere(DB::raw('CAST(device_name AS TEXT)'), 'like', '%'.$value['value'].'%');
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
        } else {
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
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Get device data by reference.
    * @param 1               $school_id number                
    * @param 2               $device_reference number 
    * @return                Array of object
    */
    public function get_device_data_by_reference($device_reference, $school_id) {
        return DB::table('devices')
                 ->select('device_id')   
                 ->where('device_reference', '=', strval($device_reference))
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }
}