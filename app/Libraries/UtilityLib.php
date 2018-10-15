<?php
namespace App\Libraries;
use Illuminate\Http\Request;
/**
 * UtilityLib Class
 *
 * @package                 Laravel Base Setup
 * @subpackage             UtilityLib
 * @category               Library
 * @DateOfCreation         13 Apr 2018
 * @ShortDescription       This Library is responsible for Utility functions that are small but usefull
 */
class UtilityLib {
    
   /**
    * @DateOfCreation        13 Apr 2018
    * @ShortDescription      This function is responsible to generate Numeric integer
    * @param                 Integer $lenght (Default Length is 6)
    * @return                Generated Numeric Integer
    */
     public function randomNumericInteger($lenght = 6){
        return ['code' => '1000','message' => __('messages.1014'),'result' => str_pad(rand(0, pow(10, $lenght)-1), $lenght, '0', STR_PAD_LEFT)];
     }
   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible to generate alphabetic string
    * @param                 Integer $lenght (Default Length is 6)
    * @return                Generated alphabetic String
    */
    public function alphabeticString($lenght = 6) {
        $alphaString = '';
        $keys = range('A', 'Z');
        for ($i = 0; $i < $lenght; $i++) {
            $alphaString .= $keys[array_rand($keys)];
        }
        return ['code' => '1000','message' => __('messages.1014'),'result' => $alphaString];
    }

   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible to generate alphanumeric string
    * @param                 Integer $length (Default length is 6)
    * @return                Generated alphanumeric string
    */
    public function alphanumericString($length = 6) {
        $alphaNumericString = '';
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++) {
            $alphaNumericString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return ['code' => '1000','message' => __('messages.1014'),'result' => $alphaNumericString];
    }

   /**
    * @DateOfCreation        13 Apr 2018
    * @ShortDescription      This function is responsible to generate alphanumeric string
    * @param                 String $text
    *                        String $searchchar
    * @return                integer
    */
    public function countCharacterInString($text,$searchchar)
    {
        if(!empty($text) && !empty($searchchar)){
            $count="0"; //zero
            for($i="0"; $i<strlen($text); $i=$i+1){
                if(substr($text,$i,1)==$searchchar){
                    $count=$count+1;
                }
            }
            return ['code' => '1000','message' => __('messages.1015'),'result' => $countreturn];
        }else{
            return ['code' => '5000','message' => __('messages.5024'),'result' => ''];
        }
    }

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible for Serialize data
    * @param                 Array $data
    * @return                String ( Serialize String )
    */

    public function getSerialize($data)
    {
        if (is_array($data) || is_object($data)) {
            return ['code' => '1000','message' => __('messages.1028'),'result' => serialize($data)];
        }else{
            return ['code' => '5000','message' => __('messages.5029'),'result' => ''];
        }
    }

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible for UnSerialize data
    * @param                 String $data
    * @return                Array
    */

    public function getUnserialize($data)
    {
        // If it isn't a string, it isn't serialized
        if (!is_string($data)) {
            return ['code' => '5000','message' => __('messages.5030'),'result' => ''];
        }

        $data = trim($data);

        // Is it the serialized NULL value?
        if ($data === 'N;') {
            return ['code' => '5000','message' => __('messages.5031'),'result' => ''];
        }

        $length = strlen($data);

        // Check some basic requirements of all serialized strings
        if ($length < 4 || $data[1] !== ':' || ($data[$length - 1] !== ';' && $data[$length - 1] !== '}')) {
            return ['code' => '5000','message' => __('messages.5032'),'result' => ''];
        }

        // $data is the serialized false value
        if ($data === 'b:0;') {
            return ['code' => '5000','message' => __('messages.5032'),'result' => ''];
        }

        // Don't attempt to unserialize data that isn't serialized
        $uns = @unserialize($data);

        // Data failed to unserialize?
        if ($uns === false) {
            $uns = @unserialize(self::fix_broken_serialization($data));

            if ($uns === false) {
                return ['code' => '5000','message' => __('messages.5032'),'result' => ''];
            } else {
                return ['code' => '1000','message' => __('messages.1029'),'result' => $uns];
            }
        } else {
            return ['code' => '1000','message' => __('messages.1029'),'result' => $uns];
        }
    }

   /**
    * @DateOfCreation        17 Apr 2018
    * @ShortDescription      This function is responsible to format the size 
    * @param                 Integer $bytes
    * @return                String
    */
    protected function formatSize($bytes){ 
        $kb = 1024;
        $mb = $kb * 1024;
        $gb = $mb * 1024;
        $tb = $gb * 1024;
        if (($bytes >= 0) && ($bytes < $kb)) {
            return $bytes . ' B';
        } elseif (($bytes >= $kb) && ($bytes < $mb)) {
            return ceil($bytes / $kb) . ' KB';
        } elseif (($bytes >= $mb) && ($bytes < $gb)) {
            return ceil($bytes / $mb) . ' MB';
        } elseif (($bytes >= $gb) && ($bytes < $tb)) {
            return ceil($bytes / $gb) . ' GB';
        } elseif ($bytes >= $tb) {
            return ceil($bytes / $tb) . ' TB';
        } else {
            return $bytes . ' B';
        }
    }

   /**
    * @DateOfCreation        17 Apr 2018
    * @ShortDescription      This function is responsible to get the size of folder 
    * @param                 Integer $bytes
    * @return                String
    */
    function folderSize($dir){
        $total_size = 0;
        $count = 0;
        $dir_array = scandir($dir);
        foreach($dir_array as $filename){
            if($filename!=".." && $filename!="."){
                if(is_dir($dir."/".$filename)){
                    $new_foldersize = $this->foldersize($dir."/".$filename);
                    $total_size = $total_size+ $new_foldersize;
                }else if(is_file($dir."/".$filename)){
                    $total_size = $total_size + filesize($dir."/".$filename);
                    $count++;
                }
            }
        }
        return $this->formatSize($total_size);
    }

   /**
    * @DateOfCreation        22 June 2018
    * @ShortDescription      This function is used for merge two array by key
    * @param                 Array $data This contains full input two array with key 
    * @return                Array
    */
    public function merge_two_arrays($array1,$array2, $key) {
        $data = array();
        $response = array();
        $arrayAB = array_merge($array1,$array2);
        foreach ($arrayAB as $value) {
            $id = $value[$key];
            if (!isset($data[$id])) {
                $data[$id] = array();
            }
            $data[$id] = array_merge($data[$id],$value);
        }
        foreach ($data as $value) {
            $response[] = $value;
        }
        return $response;
    }
}    