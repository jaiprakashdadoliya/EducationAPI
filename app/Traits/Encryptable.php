<?php 
namespace App\Traits;
use Illuminate\Support\Facades\Crypt;
use App\Libraries\SecurityLib;

/**
 * Encryptable
 *
 * @package                 Laravel Base Setup
 * @subpackage             Encryptable
 * @category               Trait
 * @DateOfCreation         20 April 2018
 * @ShortDescription       This trait is responsible to Encrypt the input and decrypt 
 *                         the output when the request is made by user
 **/
trait Encryptable
{

     /**
    * @DateOfCreation        20 Apr 2018
    * @ShortDescription      This function is responsible for Encrypt the input value 
    * @param                 String $key
    *                        String $value   
    * @return                Response (Submit attributes)
    */
    public function setAttribute($key, $value)
    {   
        $securityObj = new SecurityLib;
        if (in_array($key, $this->encryptable)) {
            $value = $securityObj->encrypt($value);
        }
        return parent::setAttribute($key, $value);
    }

    /**
    * @DateOfCreation        20 Apr 2018
    * @ShortDescription      This function is responsible for decrypt the output value 
    * @param                 String $key
    * @return                Response (Retrive attributes)
    */
    public function getAttribute($key)
    {
        $securityObj = new SecurityLib;
        if (in_array($key, $this->encryptable))
        {
            return  $securityObj->decrypt($this->attributes[$key]);
        }
        return parent::getAttribute($key);
    }

    /**
    * @DateOfCreation        20 Apr 2018
    * @ShortDescription      This function is responsible for Change attribute to array 
    * @return                Response (Retrive attributes)
    */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $securityObj = new SecurityLib;
        foreach ($attributes as $key => $value)
        {
            if (in_array($key, $this->encryptable))
            {
                $attributes[$key] = $securityObj->decrypt($value);
            }
        }
        return $attributes;
    }

} 	