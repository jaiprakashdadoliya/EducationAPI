<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DB;
use Validator;
use Carbon\Carbon;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('foreign_key_check', function($attribute, $value, $parameters)
        {
            $tableData = DB::select(DB::raw("SELECT ".$parameters[0]."_reference FROM ".$parameters[0]."s WHERE ".$parameters[0]."_reference = :referenceId AND is_deleted = :is_deleted"), array( 'referenceId' => $value, 'is_deleted' => 0));
            if (empty($tableData)) {
                return false;
            } else {
                return true;
            }
        });

        Validator::extend('check_unique_id', function($attribute, $value, $parameters)
        {
            $tableData = DB::select(DB::raw("SELECT ".$attribute." FROM ".$parameters[0]." WHERE ".$attribute." = :id"), array( 'id' => $value));
            if (empty($tableData)) {
                return false;
            } else {
                return true;
            }
        });
        
        Validator::extend('type_check', function($attribute, $value, $parameters)
        {
            $table_name  = $parameters[0];
            $reference = $parameters[1];
            $user_type   = $parameters[2];
            $tableData = DB::select(DB::raw("SELECT ".$reference." FROM ".$table_name." WHERE ".$reference." = :reference_id AND user_type = :user_type AND is_deleted = :is_deleted"), array( 'reference_id' => $value,'user_type' => $user_type,'is_deleted' => 0));
            if (empty($tableData)) {
                return false;
            } else {
                return true;
            }
        });

        Validator::extend('admin_check', function($attribute, $value, $parameters)
        {
            $table_name  = $parameters[0];
            $reference   = $parameters[1];
            $tableData   = DB::select(DB::raw("SELECT ".$reference." FROM ".$table_name." WHERE ".$reference." = :reference_id AND user_type = :user_type AND is_deleted = :is_deleted"), array( 'reference_id' => $value,'user_type' => 'admin','is_deleted' => 0));
            if (empty($tableData)) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('check_vehicle_name', function($attribute, $value, $parameters)
        {
            $school_id = $parameters[1];
            $vehicle_name = DB::table('vehicles')
             ->where($attribute, 'ILIKE', $value)
             ->where('school_id', '=', $school_id)
             ->where('is_deleted', '=', 0)
             ->count();
            if ($vehicle_name > 0) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('check_route_name', function($attribute, $value, $parameters)
        {
            $school_id = $parameters[1];
            $route_name = DB::table('routes')
             ->where($attribute, 'ILIKE', $value)
             ->where('school_id', '=', $school_id)
             ->where('is_deleted', '=', 0)
             ->count();
            if ($route_name > 0) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('check_user_driver', function($attribute, $value, $parameters)
        {
            $school_id = $parameters[1];
            $user_type = $parameters[2];
            $user_data = DB::table('users')
             ->where('user_reference', '=', $value)
             ->where('user_type', '=', $user_type)
             ->where('school_id', '=', $school_id)
             ->where('is_deleted', '=', 0)
             ->count();
            if ($user_data > 0) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('check_user_assistant', function($attribute, $value, $parameters)
        {
            $school_id = $parameters[1];
            $user_type = $parameters[2];
            $user_data = DB::table('users')
             ->where('user_reference', '=', $value)
             ->where('user_type', '=', $user_type)
             ->where('school_id', '=', $school_id)
             ->where('is_deleted', '=', 0)
             ->count();
            if ($user_data > 0) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('date_format_start_time_check', function($attribute, $value, $parameters)
        {
            $date_format= date('H:i A', strtotime($value));
            if($value == $date_format) {
                return true;
            } else {
                return false;
            }
        });

        Validator::extend('date_format_end_time_check', function($attribute, $value, $parameters)
        {
            $date_format= date('H:i A', strtotime($value));
            if($value == $date_format) {
                return true;
            } else {
                dd($value.' '.$date_format);
                return false;
            }
        });

        Validator::extend('check_device_reference', function($attribute, $value, $parameters)
        {
            $school_id = $parameters[1];
            $device_data = DB::table('devices')
             ->where('device_reference', '=', $value)
             ->where('school_id', '=', $school_id)
             ->where('is_deleted', '=', 0)
             ->count();
            if ($device_data > 0) {
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
