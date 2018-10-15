<?php

namespace App\Modules\Parents\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\News;
use App\Models\Notification;
use App\Models\Student;
use App\Models\StudentCheckIn;
use App\Libraries\FileLib;
use App\Libraries\ImageLib;
use App\Libraries\SecurityLib;
use App\Libraries\CsvLib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Traits\RestApi;
use Carbon\Carbon;
use Excel;
use Validator;
use Config;
use Schema;
use File;
use Response;
use DB;

class ParentsController extends Controller
{
    use RestApi;

   /** @var                Array $http_codes
    *  @ShortDescription   This protected member contains http status Codes
    */
    protected $http_codes = [];

   /**
    * Create a new controller instance.
    * @return void
    */
    public function __construct()
    {
        // Json array of http codes.
        $this->http_codes = $this->http_status_codes();

        // Init File Library object
        $this->file_lib = new FileLib();

        // Init Image Library object
        $this->image_lib = new ImageLib();

        // Init security library object
        $this->security_lib_obj = new SecurityLib();  

        // Init CSV Library object
        $this->csvLibObj  = new CsvLib();
    }

   /**
    * @DateOfCreation        18 July 2018
    * @ShortDescription      This is used for change password
    * @param                 Object $request This contains full request 
    * @return                Array of status and message
    */
    function change_password(Request $request)
    {
        $request_data = $this->security_lib_obj->decryptInput($request);
        $user_agent = $request->server('HTTP_USER_AGENT');
        $error = false;
        $validator = Validator::make($request_data, [
            'current_password' => 'required',
            'password'         => 'required|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?+!@_=$%^&()*-]).{8,}/',
        ]);

        if ($validator->fails()) {
            $error = true;
            $errors = $validator->errors();
        }
        if ($error == true) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                $errors,
                trans('Parents::parent.change_password_validation'), 
                $this->http_codes['HTTP_OK']
            ); 
        }

        $current_password = $request_data['current_password'];
        $password = $request_data['password'];
        if (!(Hash::check($current_password, Auth::user()->password))) {
            // The passwords matches      
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    [],
                    trans('Parents::parent.change_password_match'), 
                    $this->http_codes['HTTP_OK']
                  );

        }

        if (strcmp($current_password, $password) == 0) {
            //Current password and new password are same   
            return $this->resultResponse(
                    Config::get('restresponsecode.ERROR'), 
                    [], 
                    [],
                    trans('Parents::parent.change_password_same_password_retype'), 
                    $this->http_codes['HTTP_OK']
                  );
        }

        //Change Password
        $user = Auth::user();
        $user->password = Hash::make($password);
        $user->resource_type = $request_data['resource_type'];
        $user->ip_address = $request->ip();
        $user->user_agent = $user_agent;  
        $user->save();
        
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [], 
            [],
            trans('Parents::parent.change_password_successfull'), 
            $this->http_codes['HTTP_OK']
        );
    }

    /**
    * @DateOfCreation        18 July 2018
    * @ShortDescription      This is used for user details
    * @return                Array of status and message
    */ 
    public function user_details()
    {
        $user_id = Auth::id();
        $user_details = array();
        $user_details = User::select('name', 'picture', 'mobile', 'email')
                            ->where('users.user_id', '=', $user_id)
                            ->where('users.is_deleted', '=', 0)
                            ->first()
                            ->toArray();
        if ($user_details) {
            if (!empty($user_details['picture'])) {
                $user_details['picture'] = env('EDUCATION_APP_URL').'/api/media/user_picture/'.$user_details['picture'];
            } else {
                $user_details['picture'] = "";
            }

            $user_details['students'] = Student::select('students.student_id', 'name', 'student_picture')
                            ->join('student_parents', 'student_parents.student_id','=','students.student_id')
                            ->where('student_parents.user_id', '=', $user_id)
                            ->where('students.is_deleted', '=', 0)
                            ->get()
                            ->toArray();
            if (!empty($user_details['students'])) {
                foreach ($user_details['students'] as $key => $value) {
                    if (!empty($value['student_picture'])) {

                        $user_details['students'][$key]['student_picture'] = env('EDUCATION_APP_URL').'/api/media/student_picture/'.$value['student_picture'];
                    } else {
                        $user_details['students'][$key]['student_picture'] = env('EDUCATION_APP_URL').'/api/media/students/default_user.png';
                    }
                }
            }                            

            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [$user_details], 
                [],
                trans('Parents::parent.parent_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::parent.parent_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }

    /**
     * @DateOfCreation        18 July 2018
     * @ShortDescription      This function is responsible to get the image path
     * @param                 String $image_Name
     * @return                response
     */
    public function get_media($source, $image_name='')
    {
        $path = storage_path($source.'/').$image_name;

        //Default path set for image
        if (!File::exists($path)) {
            $path = public_path('images/'.$source.'/'.$image_name);
        }
        $file = File::get($path);
        $type = File::mimeType($path);
        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);
        $response->header('charset', 'utf-8');
        return $response;
    }

    /**
     * @DateOfCreation        18 July 2018
     * @ShortDescription      Profile photo update
     * @param                 Object $request This contains full request
     * @return                response
     */
    public function update_user_details(Request $request)
    { 
        $user_id = Auth::id();

        // Validate request
        $request_data = $request->all();
        $picture_name = '';
        //$validate = $this->image_validator($request_data);
        $validator = Validator::make($request_data, [
            'picture' => 'mimes:png,jpg,jpeg|max:4000',
            'resource_type' => 'required'
        ],[
            'picture.mimes'             => trans('Parents::parent.parent_image_format'),
            'picture.max'               => trans('Parents::parent.parent_image_max_character'),
            'resource_type.required'    => trans('Parents::parent.parent_resource_type_required')  
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
                trans('Parents::parent.parent_validation'),
                $this->http_codes['HTTP_OK']
            ); 
        }
        
        if (!empty($request['picture'])) {
            $destination = Config::get('constants.PARENTS_PHOTO_PATH');
            $file_upload = $this->file_lib->fileUpload($request['picture'], $destination);
            $picture_name = $file_upload['uploaded_file'];
            $parent = User::where('user_id', '=', $user_id)
                       ->update(array('picture' => $picture_name, 'resource_type' => $request_data['resource_type']));
        }
        
        $user_details = array();
        if ($picture_name == '') {
            $user_details['picture'] = '';

            // Select picture
            $picture_name = User::where('user_id', $user_id)->first()->picture;
            
            // Update null to picture column
            $parent = User::where('user_id', '=', $user_id)->update(array('picture' => "", 'resource_type' => $request_data['resource_type']));
            // Unlink file
            if(!empty($picture_name) && isset($picture_name)) {
                $this->file_lib->deleteFile('user_picture/'.$picture_name);
            }
            $parent_picture_upload = 'parent_picture_upload_remove';
        } else {
            $user_details['picture'] = env('EDUCATION_APP_URL').'/api/media/user_picture/'.$picture_name;
            $parent_picture_upload = 'parent_picture_upload_success';
        }
        
        // validate, is query executed successfully
        if ($parent) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [$user_details],
                [],
                trans('Parents::parent.'.$parent_picture_upload),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Parents::parent.parent_picture_upload_error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation        18 July 2018
     * @ShortDescription      This function is responsible for validating blog data
     * @param                 Array $data This contains full user media input data
     * @return                View
     */
    protected function image_validator(array $data)
    {
        $error = false;
        $errors = [];
        $validator = Validator::make($data, [
            'picture' => 'mimes:png,jpg,jpeg|max:4000'
        ]);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
     * @DateOfCreation        18 July 2018
     * @ShortDescription      Profile photo update
     * @param                 Object $request This contains full request
     * @return                response
     */
    public function update_student_details(Request $request)
    { 

        // Validate request
        $request_data = $request->all();
        //$validate = $this->image_validator($request_data);
        $validator = Validator::make($request_data, [
            'picture' => 'mimes:png,jpg,jpeg|max:4000',
            'resource_type' => 'required',
            'student_id'    => 'required'
        ],[
            'picture.mimes'             => trans('Parents::parent.parent_image_format'),
            'picture.max'               => trans('Parents::parent.parent_image_max_character'),
            'resource_type.required'    => trans('Parents::parent.parent_resource_type_required'),  
            'student_id.required'       => trans('Parents::parent.parent_student_id_required')  
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                $errors,
                trans('Parents::parent.parent_validation'),
                $this->http_codes['HTTP_OK']
            );
        }
        $picture_name = '';
        $student_id = $request_data['student_id'];
        if (!empty($request['picture'])) {
            $destination = Config::get('constants.STUDENTS_PHOTO_PATH');
            $file_upload = $this->file_lib->fileUpload($request['picture'], $destination);
            $picture_name = $file_upload['uploaded_file'];
            $student = Student::where('student_id', '=', $student_id)
                       ->update(array('student_picture' => $picture_name, 'resource_type' => $request_data['resource_type']));
        }

        $student_details = array();
        if ($picture_name == '') {
            $student_details['student_picture'] = 'default_user.png';

            // Select picture
            $picture_name = Student::where('student_id', $student_id)->first()->picture;

            // Update null to picture column
            $student = Student::where('student_id', '=', $student_id)->update(array('student_picture' => "default_user.png", 'resource_type' => $request_data['resource_type']));

            // Unlink file
            if (!empty($picture_name) && isset($picture_name)) {
                $unlinked = $this->file_lib->deleteFile('student_picture/'.$picture_name);
            }
            $student_picture_upload = 'student_picture_upload_remove';
        } else {
            $student_details['picture'] = env('EDUCATION_APP_URL').'/api/media/student_picture/'.$picture_name;
            $student_picture_upload = 'student_picture_upload_success';
        }
        // validate, is query executed successfully
        if ($student) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [$student_details],
                [],
                trans('Parents::parent.'.$student_picture_upload),
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                [],
                trans('Parents::parent.student_picture_upload_error'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        18 July 2018
    * @ShortDescription      This is used for news list.
    * @return                Array of status and message
    */ 
    public function news()
    {
        $user_id = Auth::id();
        $news_details = News::select('schools.school_id','schools.name as school_name','students.student_id','students.name as student_name','news_id','title', 'image', 'description', 'news_date')
                            ->join('schools', 'schools.school_id','=','news.school_id')
                            ->join('students', 'students.school_id','=','schools.school_id')
                            ->join('student_parents', 'student_parents.student_id','=','students.student_id')
                            ->join('users', 'users.user_id','=','student_parents.user_id')
                            ->where('student_parents.user_id', '=', $user_id)
                            ->get()->toArray();

        foreach ($news_details as $key => $value) {
            if (!empty($value['image'])) {
                $news_details[$key]['image'] = env('EDUCATION_APP_URL').'/api/media/news_picture/'.$value['image'];
            } else {
                $news_details[$key]['image'] = "";
            }
        }

        if ($news_details) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [$news_details], 
                [],
                trans('Parents::parent.student_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::parent.student_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }

    /**
    * @DateOfCreation        20 July 2018
    * @ShortDescription      This is used for getting notification settings.
    * @return                Array of status and message
    */ 
    public function get_notifications()
    {
        $user_id = Auth::id();
        $notification = User::select('notification_time')
                             ->where("user_id", '=', $user_id)
                             ->get()->toArray();
        if (!empty($notification) && isset($notification)) {

            if($notification[0]['notification_time']=='')
                $notification[0]['notification_time']=15;

            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $notification, 
                [],
                trans('Parents::parent.notification_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::parent.notification_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }

    /**
    * @DateOfCreation        20 July 2018
    * @ShortDescription      This is used for enable or disable notifications.
    * @param                 Object $request This contains full request
    * @return                Array of status and message
    */ 
    public function set_notifications(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->security_lib_obj->decryptInput($request);
        $validator = Validator::make($request_data, [
            'notification_time'   => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
                trans('Parents::parent.notification_validation'),
                $this->http_codes['HTTP_OK']
            ); 
        }
        $user = User::find($user_id);
        $user->notification_time = $request_data['notification_time'];
        $user->save();
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'), 
            [], 
            [],
            trans('Parents::parent.notification_set_successfull'), 
            $this->http_codes['HTTP_OK']
        );
    }

    /**
    * @DateOfCreation        26 July 2018
    * @ShortDescription      This function is used to fetch user notification.
    * @return                Array of status and message
    */ 
    public function notifications(Request $request)
    {
        $user_id = Auth::id();
        $selectData  =  [ 'notification_id', 'notification_type as type', 'description', 'title', 'payload', 'created_at as notification_date', 'approved_by as is_approved'];
        $whereData   =  [
                            'user_id'=> $user_id,
                            'is_deleted'=>  0
                        ];
        $notification_list = Notification::select($selectData)
                             ->where($whereData)
                             ->orderBy('notification_id', 'DESC')
                             ->offset(0)
                             ->limit(20)
                             ->get()
                             ->toArray();
        foreach($notification_list as &$not){
            $not['payload'] = array('checkin_type' => $not['type'], 'student_checkin_id' => $not['payload']);
            $not['notification_date'] = date("d/m/Y h.i A", strtotime($not['notification_date']));
            $not['is_approved'] = empty($not['is_approved']) ? 0 : 1;
            $not['title'] = empty($not['title']) ? '' : $not['title'];
        }
        if (!empty($notification_list) && isset($notification_list)) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $notification_list, 
                [],
                trans('Parents::parent.notification_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::parent.notification_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }

    /**
    * @DateOfCreation        20 July 2018
    * @ShortDescription      This is used for getting check in list.
    * @param                 Object $request This contains full request
    * @return                Array of status and message
    */ 
    public function check_in_out_lists(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->security_lib_obj->decryptInput($request);
        $validator = Validator::make($request_data, [
            'status'   => 'required'
        ],
        [
            'status.required'  => trans('Parents::parent.student_checkin_status_required')
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
                trans('Parents::parent.parent_validation'),
                $this->http_codes['HTTP_OK']
            ); 
        }
        if($request_data['status']) {
            $check_in_out_data = StudentCheckIn::select('user_id', 'student_id', 'vehicle_id', 'route_id', 'school_id', 'checkin_location_id', 'checkin_latitude', 'checkin_longitude', 'checkin_source', 'checkin_time')
                                    ->where('user_id', '=', $user_id)
                                    ->get()->toArray();
        } else {
            $check_in_out_data = StudentCheckIn::select('user_id', 'student_id', 'vehicle_id', 'route_id', 'school_id', 'checkout_location_id', 'checkout_latitude', 'checkout_longitude', 'checkout_source', 'checkout_time')
                                    ->where('user_id', '=', $user_id)
                                    ->get()->toArray();
        }
        if (!empty($check_in_out_data) && isset($check_in_out_data)) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                [$check_in_out_data], 
                [],
                trans('Parents::parent.student_check_in_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::parent.student_check_in_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }

    /**
    * @DateOfCreation        27 July 2018
    * @ShortDescription      This function is used for checkin approval.
    * @return                Array of status and message
    */ 
    public function checkin_approval(Request $request)
    {
        $user_id = Auth::id();
        $request_data = $this->security_lib_obj->decryptInput($request);
        $validator = Validator::make($request_data, [
            'student_checkin_id'   => 'required',
            'checkin_type' => 'required',
            'is_approved' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [], 
                $errors,
                trans('Parents::parent.checkin_validation'),
                $this->http_codes['HTTP_OK']
            ); 
        }
        // Update student checkin details
        if($request_data['is_approved'] == 1){
            $student_checkin = StudentCheckIn::find($request_data['student_checkin_id']);
            if($request_data['checkin_type'] == 'bus-checkin-approval'){
                $student_checkin->checkin_approved_by = $user_id;
            }

            else if($request_data['checkin_type'] == 'bus-checkout-approval'){
                $student_checkin->checkout_approved_by = $user_id;
            }
            $student_checkin->save();
            // Update approved by in notification
            $where_data = [
                               'notification_type' => $request_data['checkin_type'],
                               'payload' => $request_data['student_checkin_id'],
                               'is_deleted' => 0
                          ];
            Notification::where($where_data)->update(['approved_by' => $user_id]);
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('Parents::parent.checkin_approved_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        }
        else{
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'), 
                [], 
                [],
                trans('Parents::parent.checkin_declined_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
    * @DateOfCreation        27 July 2018
    * @ShortDescription      This function is used to get student list.
    * @return                Array of status and message
    */ 
    public function student_list(Request $request)
    {
        $user_id = Auth::id();
        $user_details = array();
        $user_details['students'] = Student::select('students.student_id', 'name', 'student_picture')
                            ->join('student_parents', 'student_parents.student_id','=','students.student_id')
                            ->where('student_parents.user_id', '=', $user_id)
                            ->where('students.is_deleted', '=', 0)
                            ->get()
                            ->toArray();
        foreach($user_details['students'] as &$stud){
            $stud['student_picture'] = !empty($stud['student_picture']) ? env('EDUCATION_APP_URL').'/api/media/student_picture/'.$stud['student_picture'] : env('EDUCATION_APP_URL').'/api/media/students/default_user.png';
        }
        if (!empty($user_details) && isset($user_details)) {
            return $this->resultResponse(
                Config::get('restresponsecode.SUCCESS'),
                $user_details, 
                [],
                trans('Parents::parent.parent_detail_successfull'), 
                $this->http_codes['HTTP_OK']
            );
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'), 
                [], 
                [],
                trans('Parents::parent.parent_detail_error'), 
                $this->http_codes['HTTP_NO_CONTENT']
            );
        }
    }
}