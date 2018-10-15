<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * StudentCheckInOut
 *
 * @subpackage             StudentCheckInOut
 * @category               Model
 * @author                 chetan  <wagh.chetan@fxbytes.com>
 * @DateOfCreation         19 July 2018
 * @ShortDescription       This model connect with the StudentCheckInOut table 
 */
class StudentCheckInOut extends Model
{
   /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'student_checkin_id';
    protected $table = 'student_checkins';

   /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'student_id', 'user_id', 'vehicle_id', 'route_id', 'school_id', 'checkin_location_id', 'checkin_latitude', 'checkin_longitude', 'checkout_location_id', 'checkout_latitude', 'checkout_longitude', 'checkin_source', 'checkout_source', 'checkin_time', 'checkout_time','created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];
}