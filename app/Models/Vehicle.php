<?php

namespace App\Models;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * Vehicle
 *
 * @subpackage             Vehicle
 * @category               Model
 * @author                 fxbytes
 * @DateOfCreation         25 July 2018
 * @ShortDescription       This model connect with the vehicles table 
 */
class Vehicle extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'vehicle_id';
    protected $table = 'vehicles'; 

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'school_id', 'vehicle_name', 'registration_number', 'chassis_number', 'vehicle_type', 'model', 'vehicle_photo', 'registration_document', 'permit_document', 'insurance_document', 'bus_capacity', 'bus_permit_validity', 'bus_insurance', 'last_maintenance', 'emergency_contact_number', 'bus_sefty_rating', 'gps_enabled', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Get vehicle list.
    * @param 1               $school_id number
    * @return                Array of object
    */
    public function get_vehicles($requestData) {
        $selectData  =  ['vehicle_id','vehicle_reference','vehicle_name','registration_number','emergency_contact_number','chassis_number','bus_capacity'];
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
                                ->orWhere(DB::raw('CAST(vehicles.vehicle_name AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.registration_number AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.chassis_number AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.bus_capacity AS TEXT)'), 'ILIKE', $value['value'])
                                ->orWhere(DB::raw('CAST(vehicles.emergency_contact_number AS TEXT)'), 'ILIKE', $value['value']);
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
                    ->map(function ($parentList) {
                        return $parentList;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Get vehicle list for dropdow selection.
    * @param 1               $school_id number
    * @return                Array of object
    */
    public function get_vehicles_list_dropdown($requestData) {
        $selectData  =  ['vehicle_id as value', 'registration_number as label'];
        $whereData   =  [
                        'school_id'=> $requestData['school_id'],
                        'is_deleted'=>  0
                        ];
        return $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->get();
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      Get vehicle list for dropdow selection.
    * @param 1               $school_id number
    * @return                Array of object
    */
    public function get_vehicle_data_by_reference($school_id, $vehicle_reference) {
        return DB::table('vehicles')
                 ->where('vehicle_reference', '=', $vehicle_reference)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Get vehicle data.
    * @param 1               $school_id number
    * @param 1               $vehicle_name string
    * @return                Array of object
    */
    public function get_vehicle_data($vehicle_name, $school_id) {
        return DB::table('vehicles')
                 ->select('vehicle_id')
                 ->where('vehicle_name', 'ILIKE', $vehicle_name)
                 ->where('school_id', '=', $school_id)
                 ->where('is_deleted', '=', 0)
                 ->first();
    }
}
