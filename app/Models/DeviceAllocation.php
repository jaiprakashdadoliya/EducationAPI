<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * Device Allocation
 *
 * @subpackage             Device Allocation
 * @category               Model
 * @DateOfCreation         17 July 2018
 * @ShortDescription       This model connect with the Device Allocation table 
 */
class DeviceAllocation extends Model
{
     use HasApiTokens, Notifiable;

     /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'device_vehicle_id';
    protected $table = 'device_vehicles';

     /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'device_id', 'vehicle_id', 'school_id', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

     /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Get device allocation list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_device_allocation($requestData) {
        $selectData  =  ['vehicles.registration_number', 'devices.device_name'];
        $whereData   =  [
                            $this->table.'.school_id'=> $requestData['school_id'],
                            $this->table.'.is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                    ->join('devices', 'devices.device_id', '=', $this->table.'.device_id')
                    ->join('vehicles', 'vehicles.vehicle_id', '=', $this->table.'.vehicle_id')
                    ->select($selectData)
                    ->where($whereData);

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
    * @ShortDescription      Get device allocation data by reference.
    * @param 1               $school_id number                
    * @param 2               $device_vehicle_reference number
    * @return                Array of object
    */    
    public function get_device_vehicle_data_by_reference($school_id, $device_vehicle_reference) {
        return DB::table('device_vehicles')
                 ->where('device_vehicle_reference', '=', $device_vehicle_reference)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }

    /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Insert data.
    * @param 1               $school_id number                
    * @param 2               $device_vehicle_reference number
    * @return                boolean
    */    
    public function insert_device_vehicle($insert_data) {
        DB::table('device_vehicles')->insert($insert_data);
        return true;
    }

    /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Update data.
    * @param 1               $device_vehicle_id number                
    * @param 2               $updated_data array
    * @return                boolean
    */    
    public function update_device_vehicle($updated_data, $device_vehicle_id) {
        return DB::table('device_vehicles')
            ->where('device_vehicle_id','=',$device_vehicle_id)
            ->where('is_deleted','=',0)
            ->update($updated_data);
    }

     /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      Delete data.
    * @param 1               $device_vehicle_id number                
    * @param 2               $delete_data array
    * @return                boolean
    */    
    public function delete_data($delete_data ,$device_vehicle_id) {
        return DB::table('device_vehicles')
            ->where('device_vehicle_id','=',$device_vehicle_id)
            ->where('is_deleted','=',0)
            ->update($delete_data);
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Update device vehicle.
    * @param 1               $school_id number                
    * @param 2               $device_id number 
    * @param 3               $device_route_array array
    * @return                Array of object
    */
    public function update_device_vehicle_csv_data($device_route_array, $device_id, $school_id) {
        return DB::table('device_vehicles')
                 ->where('device_id', '=', $device_id)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->update($device_route_array);
    }
}
