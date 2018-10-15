<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Student Check In
 *
 * @subpackage             Student Check In
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         20 July 2018
 * @ShortDescription       This model connect with the student_check_in  table 
 */

class StudentCheckIn extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'student_checkin_id';
    protected $table = 'student_checkins'; 

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id','student_id', 'vehicle_id', 'route_id', 'school_id', 'checkin_location_id', 'checkout_location_id', 'checkin_latitude', 'checkin_longitude', 'checkout_latitude', 'checkout_longitude', 'checkin_source', 'checkout_source', 'checkin_time', 'checkout_time', 'resource_type', 'user_agent', 'ip_address', 'is_deleted', 'created_at', 'updated_at','created_by','updated_by'
    ];
}
