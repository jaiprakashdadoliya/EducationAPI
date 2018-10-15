<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\Encryptable;

/**
 * PasswordReset Class
 *
 * @subpackage             PasswordReset
 * @category               Model
 * @DateOfCreation         24 May 2018
 * @ShortDescription       This is model which need to perform the options related to PasswordReset table
 */

class PasswordReset extends Model {
    
    use Notifiable,HasApiTokens,Encryptable;

    // @var Array $encryptedFields
    // This protected member contains fields that need to encrypt while saving in database
    protected $encryptable = [];
   
     // @var string $table
    // This protected member contains primary key
    protected $primaryKey = 'email';

     // @var string $table
    // This protected member contains table name
    protected $table = 'password_resets';
}