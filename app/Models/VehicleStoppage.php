<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * VehicleStoppage
 *
 * @subpackage             VehicleStoppage
 * @category               Model
 * @author                 chetan  <wagh.chetan@fxbytes.com>
 * @DateOfCreation         25 July 2018
 * @ShortDescription       This model connect with the vehicle routes table 
 */
class VehicleStoppage extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'vehicle_stoppage_id';
    protected $table = 'vehicle_stoppages'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vehicle_id', 'school_id', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];
    
}

