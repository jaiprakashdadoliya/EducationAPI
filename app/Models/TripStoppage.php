<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;
class TripStoppage extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'trip_stoppage_id';
    protected $table = 'trip_stoppages'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'trip_id','stoppage_id', 'reaching_time', 'school_id', 'created_by', 'updated_by', 'resource_type', 'user_agent', 'ip_address', 'is_deleted','created_at', 'updated_at'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
    * @DateOfCreation        08 August 2018
    * @ShortDescription      Trip stoppage passed data.
    * @param 1               $trip_id number                
    * @param 2               $school_id number                
    * @return                Array of object
    */
    public function get_trip_stoppage_passed_data($trip_id, $school_id){
        $date = date('Y-m-d');
        $query =  DB::table($this->table)
                            ->select('trip_stoppage_id','stoppage_id')
                            ->where('trip_id','=',$trip_id)
                            ->where('is_deleted','=',0)
                            ->where('school_id','=',$school_id)
                            ->whereDate('created_at','=',$date)
                            ->first();
    }
}
