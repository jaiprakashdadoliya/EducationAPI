<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * User
 *
 * @subpackage             User
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         17 July 2018
 * @ShortDescription       This model connect with the Users table 
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'user_id';
    protected $table = 'users'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_reference','name', 'password', 'email', 'mobile', 'school_id', 'address', 'state', 'city', 'postcode', 'aadhar_number','driving_licence_number', 'picture', 'user_type','driver_police_verification','created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
    * @DateOfCreation        18 July 2018
    * @ShortDescription      Get the Access token on behalf of user id 
    * @return                Array
    */
    public function oauth_access_token(){
         return $this->hasMany('\App\Models\OauthAccessToken','user_id','user_id');
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Get parent list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_users($requestData) {
        
        $selectData  =  ['user_id','user_reference','name','email','mobile','address','state','city','postcode'];
        $whereData   =  [
                        'school_id'=> $requestData['school_id'],
                        'user_type'=> $requestData['user_type'],
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
                                ->orWhere(DB::raw('CAST(name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(email AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(address AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(state AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(city AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(mobile AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(postcode AS TEXT)'), 'like', '%'.$value['value'].'%');
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
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Get staff list.
    * @param 1               $school_id number                
    * @return                Array of object
    */
    public function get_staff($requestData) {
        
        $selectData  =  ['user_id','user_reference','name','user_type','email','mobile','aadhaar_number','driving_licence_number'];
        $whereData   =  [
                        'school_id'=> $requestData['school_id'],
                        'is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->whereIn('user_type', $requestData['user_type']);
        /* Condition for Filtering the result */
        if(!empty($requestData['filtered'])){
            foreach ($requestData['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(name AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(email AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(address AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(state AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(city AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(mobile AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(postcode AS TEXT)'), 'like', '%'.$value['value'].'%');
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
    * @DateOfCreation        07 August 2018
    * @ShortDescription      Get user detail.
    * @param 1               $user_reference number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_user_detail_by_reference($user_reference, $school_id) {
        return DB::table('users')
                ->select('user_id')    
                ->where('user_reference', '=', $user_reference)
                ->where('school_id', '=', $school_id)
                ->where('is_deleted', '=', 0)
                ->first();
    }

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Get user driver detail.
    * @param 1               $user_reference number                
    * @param 2               $school_id number                
    * @param 3               $user_type string
    * @return                Array of object
    */
    public function get_user_data_by_type($user_reference, $school_id, $user_type) {
        return DB::table('users')
                ->select('user_id')    
                ->where('user_reference', '=', $user_reference)
                ->where('school_id', '=', $school_id)
                ->where('user_type', '=', $user_type)
                ->where('is_deleted', '=', 0)
                ->first(); 
    }

}