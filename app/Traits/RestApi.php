<?php 
namespace App\Traits;
use Response;
use Config;
use App\Libraries\SecurityLib;
use App\Providers\ResponseMacroServiceProvider;
use Illuminate\Http\Request;
/**
 * RestApi
 *
 * @package                Laravel Base Setup
 * @subpackage             RestApi
 * @category               Trait
 * @DateOfCreation         23 April 2018
 * @ShortDescription       This trait is responsible to Access the config of rest 
 *                         and also generate the response for each request
 **/
trait RestApi
{
    /**
    * @DateOfCreation        23 Apr 2018
    * @ShortDescription      This function is responsible to get the rest_configration 
    * @return                Array 
    */
    protected function rest_config(){
        return Config::get('rest.rest_config');
    }

    /**
    * @DateOfCreation        23 Apr 2018
    * @ShortDescription      This function is responsible to get the http_status codes  
    * @return                Array
    */
    protected function http_status_codes(){
        return Config::get('rest.http_status_codes');
    }
    
    /**
    * @DateOfCreation        10 Apr 2018
    * @ShortDescription      This function is responsible for getting full request data 
    */
    protected function getRequestData($request){
        return $request->all();
    }

    /**
    * @DateOfCreation        23 Apr 2018
    * @ShortDescription      This function is responsible for generating the resposnse for each array 
    * @param                 Integer $code
    *                        Array $data
    *                        String $error - Default "Unknown Error"
    *                        String $msg
    *                        Integer $http_status - Default 3000   
    * @return                Response (Submit attributes)
    */
     protected function resultResponse($code, $data, $errors = [], $msg, $http_status = 3000)
     {  
        $debug= app('request')->header('unencrypted');

        $rest_status_field    =  Config::get('rest.rest_config.rest_status_field_name');
        $rest_data_field      =  Config::get('rest.rest_config.rest_data_field_name');
        $rest_message_field   =  Config::get('rest.rest_config.rest_message_field_name');
        $rest_error_field     =  Config::get('rest.rest_config.rest_error_field_name');
        $rest_http_status     =  Config::get('rest.rest_config.rest_http_status_field_name');
        $rest_config          =  $this->rest_config();
        
        if($rest_config['rest_default_format'] == 'json'){
            $this->securityLibObj = new SecurityLib();  
            $json=[
                $rest_status_field => $code,
                $rest_data_field => $data,
                $rest_message_field => $msg,
                $rest_error_field => $errors,
                $rest_http_status => $http_status
            ]; 
            if($debug){
                $response = response()->json($json);                   
            }else{                             
                $response = response()->encrypt($json);
            }
            
        }
        if($rest_config['rest_default_format'] == 'xml'){
            $response = response()->xml([
                $rest_status_field => $code,
                $rest_data_field => $data,
                $rest_message_field => $msg,
                $rest_error_field => $errors,
                $rest_http_status => $http_status
            ]);
        }
        return $response;
     }

     /**
    * @DateOfCreation        05 June 2018
    * @ShortDescription      This function is used for admin and responsible for generating the resposnse for each array 
    * @param                 Integer $code
    *                        Array $data
    *                        String $error - Default "Unknown Error"
    *                        String $msg
    *                        Integer $http_status - Default 3000   
    * @return                Response (Submit attributes)
    */
     protected function echoResponse($code, $data, $errors = [], $msg, $http_status = 3000)
     {  

        $rest_status_field    =  Config::get('rest.rest_config.rest_status_field_name');
        $rest_data_field      =  Config::get('rest.rest_config.rest_data_field_name');
        $rest_message_field   =  Config::get('rest.rest_config.rest_message_field_name');
        $rest_error_field     =  Config::get('rest.rest_config.rest_error_field_name');
        $rest_http_status     =  Config::get('rest.rest_config.rest_http_status_field_name');
        $rest_config          =  $this->rest_config();
        
        if($rest_config['rest_default_format'] == 'json'){
            $this->securityLibObj = new SecurityLib();  
            $json=[
                $rest_status_field => $code,
                $rest_data_field => $data,
                $rest_message_field => $msg,
                $rest_error_field => $errors,
                $rest_http_status => $http_status
            ]; 
            $response = response()->json($json);      
        }
        if($rest_config['rest_default_format'] == 'xml'){
            $response = response()->xml([
                $rest_status_field => $code,
                $rest_data_field => $data,
                $rest_message_field => $msg,
                $rest_error_field => $errors,
                $rest_http_status => $http_status
            ]);
        }
        return $response;
     }

} 	