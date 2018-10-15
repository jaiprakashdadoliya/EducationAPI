<?php
namespace App\Libraries;
use Illuminate\Http\Request;
use Config;
use App\Traits\RestApi;

/**
 * SecurityLib Class
 * @package                 Laravel Base Setup
 * @subpackage             SecurityLib
 * @category               Library
 * @DateOfCreation         05 Apr 2018
 * @ShortDescription       This Library is responsible for all security functions
 */
class SecurityLib {
    
    use RestApi;

    // @var String $secret_key1
    // This protected member contains first secret key for encryption
    protected $secret_key1 = '';
    
    // @var String $secret_key2
    // This protected member contains second secret key for encryption
    protected $secret_key2 = '';
    
    // @var String $encrypt_method
    // This protected member contains encrypted method
    protected $encrypt_method = "AES-256-CBC";

    // @var Array $blacklist_ip
    // This protected member contains array of blacklist ip's
    protected $blackListIps = [];
    
    // @var Array $whitelist_ip
    // This protected member contains array of whitelist ip's
    protected $whiteListIps = [];
    /**
     * Create a new library instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->secret_key1  = Config::get('constants.ENCRYPTION_KEY1');
        $this->secret_key2  = Config::get('constants.ENCRYPTION_KEY2');
        $this->blackListIps = Config::get('iplist.blacklist_ip');
        $this->whiteListIps = Config::get('iplist.whitelist_ip');
    }
   
    
   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible for decrypting a string
    * @param                 String $stringToEncrypt any string to encrypt 
    * @return                Encrypted string
    */
    public function encrypt($stringToEncrypt, $request=null) {
      
        //Debug mode
        if($request!=null)
            $action = $request->header('debug');
        else
            $action = 0;

        $key1 = $this->secret_key1;
        $key2 = $this->secret_key2;
        if ($action == 0) {
            $stringToEncrypt = openssl_encrypt( $stringToEncrypt, $this->encrypt_method, $key1, 0, $key2 );
        }
        return $stringToEncrypt;
    }   

    
   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is use to decrypt input raw data
    * @param                 String $stringToDecrypt any string to decrypt
    * @return                Decrypted string
    */
    public function decrypt($stringToDecrypt, $request=null) {
        
        //Debug mode
        if($request!=null)
            $action = $request->header('debug');
        else
            $action = 0;
        
        $key1 = $this->secret_key1;
        $key2 = $this->secret_key2;
        if ($action == 0) {
            $stringToDecrypt = openssl_decrypt($stringToDecrypt, $this->encrypt_method, $key1,0, $key2);
        }
        return $stringToDecrypt;
    }

   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible to check blacklist ip
    * @param                 String $ip
    * @return                Boolean (true/false) 
    */
    public function isIpBlackListed($ip)
    {
        $output = false;
        $blackList = $this->blackListIps;
        $output = in_array($ip,$blackList);
        return $output;
    }

   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible to check whitelist ip
    * @param                 String $ip
    * @return                Boolean (true/false) 
    */
    public function isIpWhiteListed($ip)
    {
        $output = false;
        $whiteList = $this->whiteListIps;
        $output = in_array($ip,$whiteList);
        return $output;
    }

   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible to generate random otp
    * @param                 Integer $lenghtOfOtp
    *                        Integer $typeOfOtp (1 for numeric, 2 for alpha, 3 alphanumeric)
    * @return                Generated OTP
    */
    public function genrateRandomOTP($lenghtOfOtp,$typeOfOtp)
    {
        $utilitylibObj = new UtilityLib();
        $otp = '';
        switch ($lenghtOfOtp) {
            case 1:
                $otp = $utilitylibObj->randomNumericInteger($lenghtOfOtp);
            break;
            case 2:
                $otp = $utilitylibObj->alphabeticString($lenghtOfOtp);
            break;
            case 3:
                $otp = $utilitylibObj->alphanumericString($lenghtOfOtp);
            break;
        }
        return $otp;
            
    }

   /**
    * @DateOfCreation        17 May 2018
    * @ShortDescription      This function is used for get Raw input
    * @return                Get Array
    */
    public function decryptInput($request)
    {
        //Debug mode
        $action = $request->header('unencrypted');
        if(empty($action)){
            $action=0;
        }
   
        if ($action == 0) {
            $input= json_decode($this->decrypt($request->getContent()),true);     
        }else{
            $input= json_decode($request->getContent(),true);     
        }    

        $request = new Request();

        $request->replace($input);
        return $this->getRequestData($request);
           
    }

   /**
    * @DateOfCreation        24 May 2018
    * @ShortDescription      This function is responsible to generate random password
    *                        It contain at least one uppercase/lowercase letters, number, one special character and minimum 8 characters.     
    * @return                Generating 8 character password.
    */

    public function genrateRandomPassword(){    
        function lower( $length = 2 ) {
            $chars = "abcdefghijklmnopqrstuvwxyz";
            $password = substr( str_shuffle( $chars ), 0, $length );
            return $password;
        }
        function upper( $length = 2 ) {
            $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $password = substr( str_shuffle( $chars ), 0, $length );
            return $password;
        }
        function number( $length = 3 ) {
            $chars = "0123456789";
            $password = substr( str_shuffle( $chars ), 0, $length );
            return $password;
        }
        function charcter( $length = 1 ) {
            $chars = "!@#$%^&*()_-=+?";
            $password = substr( str_shuffle( $chars ), 0, $length );
            return $password;
        }
        $str = lower().upper().number().charcter();
        return str_shuffle($str);
    }

}