<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Profile photo
Route::get('media/{source}/{image_name}', '\App\Modules\Parents\Controllers\ParentsController@get_media');

// Auth module
Route::group(['module' => 'Auth', 'middleware' => ['api'], 'namespace' => 'App\Modules\Auth\Controllers'], function() {
	//Login
	Route::post('login', '\App\Modules\Auth\Controllers\AuthController@post_login');
	
	//Logout
	Route::post('logout', '\App\Modules\Auth\Controllers\AuthController@logout');

	//Admin Login
	Route::post('admin_login', '\App\Modules\Auth\Controllers\AuthController@admin_login');

	//Logout
	Route::post('admin_logout', '\App\Modules\Auth\Controllers\AuthController@admin_logout');
	
	//Assistant Login
	Route::post('assistant/login','\App\Modules\Auth\Controllers\AuthController@assistant_login');

	// Verify Assistant otp
	Route::post('assistant/otp_verification','\App\Modules\Auth\Controllers\AuthController@verify_assistant_otp');

	// Assistant logout
	Route::post('assistant/logout','\App\Modules\Auth\Controllers\AuthController@assistant_logout');

	//Reset Password
	Route::post('forgot_password', '\App\Modules\Auth\Controllers\AuthController@get_reset_password');

});


// Parents module
Route::group(['module' => 'Parents', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\Parents\Controllers'], function() {

	//Change Password
	Route::post('change_password', '\App\Modules\Parents\Controllers\ParentsController@change_password');

	//User Details
	Route::post('user_details', '\App\Modules\Parents\Controllers\ParentsController@user_details');

	//User Details
	Route::post('update_user_details', '\App\Modules\Parents\Controllers\ParentsController@update_user_details');

	//User Details
	Route::post('update_student_details', '\App\Modules\Parents\Controllers\ParentsController@update_student_details');

	//Check in out
	Route::post('check_in_out', '\App\Modules\Parents\Controllers\EventController@check_in_out');

	//News Details
	Route::post('news', '\App\Modules\Parents\Controllers\ParentsController@news');

	//Get Notification
	Route::post('get_notifications', '\App\Modules\Parents\Controllers\ParentsController@get_notifications');

	//Set Notification
	Route::post('set_notifications', '\App\Modules\Parents\Controllers\ParentsController@set_notifications');

	//Set Notification
	Route::post('notifications', '\App\Modules\Parents\Controllers\ParentsController@notifications');

	//Check in or out lists
	Route::post('check_in_out_lists', '\App\Modules\Parents\Controllers\ParentsController@check_in_out_lists');


	//Student absent
	Route::post('add_absent', '\App\Modules\Parents\Controllers\AbsentController@add_absent');

	//Student absent
	Route::post('delete_absent', '\App\Modules\Parents\Controllers\AbsentController@delete_absent');

	//Calender event
	Route::post('parent/calendar', '\App\Modules\Parents\Controllers\AbsentController@get_absent_list');

	// Bus assistant
	Route::post('bus_assistant', '\App\Modules\Parents\Controllers\ParentsController@bus_assistant');

	//Dashboard
	Route::post('dashboard','\App\Modules\Parents\Controllers\DashboardController@dashboard');

	// Checkin Approval
	Route::post('parent/checkin_approval', '\App\Modules\Parents\Controllers\ParentsController@checkin_approval');

	//Notifications
	Route::post('parent/notifications','\App\Modules\Parents\Controllers\ParentsController@notifications');

	// Student List
	Route::post('parent/student_list','\App\Modules\Parents\Controllers\ParentsController@student_list');

	// Route List
	Route::post('parent/route_list','\App\Modules\Parents\Controllers\DashboardController@route_list');

});

// Assitant module
Route::group(['module' => 'Assistant', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\Assistant\Controllers'], function() {

	//checkin approval
	Route::post('assistant/checkin','\App\Modules\Assistant\Controllers\AssistantController@checkin');
	
	// Notifications
	Route::post('assistant/notifications','\App\Modules\Assistant\Controllers\AssistantController@notifications');

	// SOS Messaging service
	Route::post('assistant/sos','\App\Modules\Assistant\Controllers\AssistantController@sos');

	// Student image
	Route::post('assistant/student_image','\App\Modules\Assistant\Controllers\AssistantController@student_image');

	// Trip Start/End
	Route::post('assistant/trip_start','\App\Modules\Assistant\Controllers\AssistantController@trip_status');

	//send apn notification
	Route::post('assistant/send_apn_notification','\App\Modules\Assistant\Controllers\AssistantController@send_apn_notification');
});
// Admin module
Route::group(['module' => 'Admin', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\Admin\Controllers'], function() {
	//Parents
	Route::post('parents', '\App\Modules\Admin\Controllers\MasterController@parents');
	//Parent password send by email
	Route::post('create_password', '\App\Modules\Admin\Controllers\MasterController@create_password');
	//Stoppage
	Route::post('stoppage', '\App\Modules\Admin\Controllers\MasterController@stoppage');
	//Students
	Route::post('students', '\App\Modules\Admin\Controllers\MasterController@students');
	//Vehicles
	Route::post('vehicles', '\App\Modules\Admin\Controllers\MasterController@vehicles');
	//Vehicles list for dropdown
	Route::post('vehicle_list', '\App\Modules\Admin\Controllers\BusManagementController@get_vehicle_list');
	//Staff
	Route::post('staff', '\App\Modules\Admin\Controllers\MasterController@staff');
	//Student Allocation
	Route::post('student_allocation', '\App\Modules\Admin\Controllers\BusManagementController@student_allocation');
	//Staff Allocation
	Route::post('staff_allocation', '\App\Modules\Admin\Controllers\BusManagementController@staff_allocation');
	//Route Allocation
	Route::post('route_allocation', '\App\Modules\Admin\Controllers\BusManagementController@route_allocation');
	//Stoppage List
	Route::post('stoppage_list', '\App\Modules\Admin\Controllers\BusManagementController@stoppage_list');
	//Route List
	Route::post('route_list', '\App\Modules\Admin\Controllers\BusManagementController@route_list');
	//Route Stoppage List
	Route::post('route_stoppage', '\App\Modules\Admin\Controllers\BusManagementController@route_stoppage');

	//Student
	Route::post('import_student', '\App\Modules\Admin\Controllers\BusManagementController@student_import_save');

	// Import route stoppage
	Route::post('import_route_stoppage', '\App\Modules\Admin\Controllers\MasterController@route_stoppage_import_save');

	// Stoppage save
	Route::post('import_stoppage', '\App\Modules\Admin\Controllers\BusManagementController@stoppage_import_save');

	// Device save
	Route::post('import_device_allocation', '\App\Modules\Admin\Controllers\MasterController@device_allocation_import_save');

	// Device list
	Route::post('device_list', '\App\Modules\Admin\Controllers\MasterController@device');

	// Device Allocation list
	Route::post('device_allocation_list', '\App\Modules\Admin\Controllers\MasterController@device_allocation_import_save');

	// CSV validation for route stoppage
	Route::post('master/route_stoppage_validation','\App\Modules\Admin\Controllers\MasterController@route_stoppage_validation');
	
	// Route Path save
	Route::post('route_path_save', '\App\Modules\Admin\Controllers\MapController@route_path_save');
	// Route Path draw
	Route::post('route_path_draw', '\App\Modules\Admin\Controllers\MapController@route_path_draw');

	// Student Route Allocation
	Route::post('import_student_allocation', '\App\Modules\Admin\Controllers\BusManagementController@import_student_allocation');

	// Schedule routes
	Route::post('schedule_route', '\App\Modules\Admin\Controllers\MasterController@schedule_route');

	// Schedule route import save.
	Route::post('import_schedule_route', '\App\Modules\Admin\Controllers\BusManagementController@import_schedule_route_save');

	// Beacons List
	Route::post('beacons', '\App\Modules\Admin\Controllers\MasterController@beacons');

	// Schedule route import save.
	Route::post('csv_validation_schedule_route', '\App\Modules\Admin\Controllers\BusManagementController@csv_validation_schedule_route');

});

// CSVImportExport module
Route::group(['module' => 'CSVImportExport', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\CSVImportExport\Controllers'], function() {
	//CSV import validation.
	Route::post('import_validation','\App\Modules\CSVImportExport\Controllers\CSVImportController@import_validation');

	//CSV import save.
	Route::post('import_save','\App\Modules\CSVImportExport\Controllers\CSVImportController@import_save');

});

//Export csv files
Route::get('export_file/{user_id}/{export_name}','\App\Modules\CSVImportExport\Controllers\CSVImportController@export_file');

//Export csv files
Route::get('export_studentAllocation/{user_id}','\App\Modules\Admin\Controllers\BusManagementController@export_studentAllocation');

//Export csv files
Route::get('export_staffAllocation/{user_id}','\App\Modules\Admin\Controllers\BusManagementController@export_staffAllocation');

//Export csv
Route::get('export_scheduleRoute/{user_id}', '\App\Modules\Admin\Controllers\MasterController@export_scheduleRoute');
