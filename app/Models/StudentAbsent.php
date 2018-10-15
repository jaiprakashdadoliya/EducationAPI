<?php

namespace App\Models;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * Student Absent
 *
 * @subpackage             Student Absent
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         23 July 2018
 * @ShortDescription       This model connect with the student_absents table 
 */
class StudentAbsent extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'student_absent_id';
    protected $table = 'student_absents'; 

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id', 'student_id', 'school_id', 'absent_date', 'absent_type', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        23 July 2018
    * @ShortDescription      Check student absent marked.
    * @param                 Object $request This contains full request 
    * @return                Array
    */
    public function check_student_absent_marked($user_id, $student_id, $absent_date) {
        return DB::table('student_absents')
                ->where('user_id', '=', $user_id)
                ->where('student_id', '=', $student_id)
                ->where('absent_date', '=', $absent_date)
                ->where('is_deleted', '=', 0)
                ->count();
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Get student absent.
    * @param 1               $user_id number                
    * @param 2               $year number                
    * @param 3               $month number                
    * @return                Array of object
    */
    public function get_student_absents($user_id, $year, $month) {
        return DB::table('student_absents')
                ->where('user_id', '=', $user_id)
                ->whereYear('absent_date', '=', $year)
                ->whereMonth('absent_date', '=', $month)
                ->where('is_deleted', '=', 0)
                ->get()->toArray();
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Get student absent.
    * @param 1               $user_id number                
    * @param 2               $year number                
    * @param 3               $month number                
    * @return                Array of object
    */
    public function get_single_student_absents($user_id, $year, $month, $student_id) {
        return DB::table('student_absents')
                ->select('student_absent_id', 'absent_date', 'absent_type')
                ->where('user_id', '=', $user_id)
                ->where('student_id', '=', $student_id)
                ->whereYear('absent_date', '=', $year)
                ->whereMonth('absent_date', '=', $month)
                ->where('is_deleted', '=', 0)
                ->get()->toArray();
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Get student absent.
    * @param 1               $student_absent_id number                
    * @return                Array of object
    */
    public function get_student_absent_by_id($student_absent_id) {
        return  DB::table('student_absents')
                ->where('student_absent_id', '=', $student_absent_id)
                ->where('is_deleted', '=', 0)
                ->first();
    }

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Update student absent.
    * @param 1               $student_absent_id number                
    * @param 2               $update_array array                
    * @return                Boolean
    */
    public function update_student_absent($update_array,$student_absent_id) {
        DB::table('student_absents')
            ->where('student_absent_id', $student_absent_id)
            ->update($update_array);
    }

    /**
    * @DateOfCreation        23 July 2018
    * @ShortDescription      Insert student absent.
    * @param                 Array
    * @return                Boolean
    */
    public function insert($insert_array) {
        $result = DB::table('student_absents')->insert($insert_array);
    }   

    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Check absent date for dashboard.
    * @param 1               $student_id number                
    * @return                Array of object
    */
    public function check_absent_in_dashboard($request) {
        return  DB::table($this->table)
                    ->select('student_absent_id', 'absent_type')
                    ->where('student_id', '=', $request['student_id'])
                    ->whereDate('absent_date', '=', $request['date'])
                    ->where('is_deleted', '=', 0)
                    ->first();
    }   
}
