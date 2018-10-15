<?php

namespace App\Models;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * Student
 *
 * @subpackage             Student
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         19 July 2018
 * @ShortDescription       This model connect with the students table 
 */
class Student extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'student_id';
    protected $table = 'students'; 

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'name', 'student_reference', 'class', 'student_picture', 'school_id', 'created_by', 'updated_by', 'resource_type', 'ip_address', 'user_agent', 'is_deleted'
    ];

    /**
    * @DateOfCreation        24 July 2018
    * @ShortDescription      Get student details.
    * @param                 $student_id number
    * @return                Array of object
    */
    public function get_student_details($student_id) {
        return DB::table('students')
                ->where('student_id', '=', $student_id)
                ->where('is_deleted', '=', 0)
                ->first();
    }
    /**
    * @DateOfCreation        25 July 2018
    * @ShortDescription      Get student list.
    * @param 1               $school_id number
    * @return                Array of object
    */

    public function get_student($request_data) {
        $selectData  =  ['students.student_id','students.student_reference','students.name', 'students.class','users.user_reference','users.name as parent_name'];
        $whereData   =  [
                        'students.school_id'=> $request_data['school_id'],
                        'students.is_deleted'=>  0
                        ];
        $query =  DB::table($this->table)
                    ->select($selectData)
                    ->where($whereData)
                    ->join('student_parents', 'students.student_id', '=', 'student_parents.student_id')
                    ->join('users', 'student_parents.user_id', '=', 'users.user_id');

        /* Condition for Filtering the result */
        if(!empty($request_data['filtered'])){
            foreach ($request_data['filtered'] as $key => $value) {
                $query = $query->where(function ($query) use ($value){
                                $query
                                ->orWhere(DB::raw('CAST(students.student_reference AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(students.name AS TEXT)'), 'ILIKE',$value['value'])
                                ->orWhere(DB::raw('CAST(students.class AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(users.user_reference AS TEXT)'), 'like', '%'.$value['value'].'%')
                                ->orWhere(DB::raw('CAST(users.name AS TEXT)'), 'like', '%'.$value['value'].'%');
                            });
            }
        }

        /* Condition for Sorting the result */
        if(!empty($request_data['sorted'])){
            foreach ($request_data['sorted'] as $key => $value) {
                $orderBy = $value['desc'] ? 'desc' : 'asc';
                $query = $query->orderBy($value['id'], $orderBy);
            }
        }
        if($request_data['page'] > 0){
            $offset = $request_data['page']*$request_data['pageSize'];
        }else{
            $offset = 0;
        }
        
        $Data['pages'] = ceil($query->count()/$request_data['pageSize']);
        $Data['data'] = $query
                    ->offset($offset)
                    ->limit($request_data['pageSize'])
                    ->get()
                    ->map(function ($parentList) {
                        return $parentList;
                    });
        return $Data;
    }

    /**
    * @DateOfCreation        27 July 2018
    * @ShortDescription      Get student details.
    * @param                 $student_id number
    * @return                Array of object
    */
    public function get_student_details_by_reference_id($student_reference_id, $school_id) {
        return DB::table('students')
                ->where('student_reference', '=', $student_reference_id)
                ->where('school_id', '=', $school_id)
                ->where('is_deleted', '=', 0)
                ->first();
    }

    /**
    * @DateOfCreation        27 July 2018
    * @ShortDescription      Update student details.
    * @param                 $student_id number
    * @return                Boolean
    */
    public function update_student($student_id, $student_array) {
        return DB::table('students')
                    ->where('student_id', $student_id)
                    ->update($student_array);
    }

    /**
    * @DateOfCreation        27 July 2018
    * @ShortDescription      Update student parent details.
    * @param 1                $student_id number number
    * @param 2                $student_parent_array array
    * @return                Boolean
    */
    public function update_student_parent($student_id, $student_parent_array) {
        return DB::table('student_parents')
                    ->where('student_id', $student_id)
                    ->update($student_parent_array);
    }

}
