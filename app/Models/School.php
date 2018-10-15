<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * School
 *
 * @subpackage             School
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         17 July 2018
 * @ShortDescription       This model connect with the Schools table 
 */
class School extends Model
{
    use HasApiTokens, Notifiable;

    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'school_id';
    protected $table = 'schools';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'logo', 'contact_first_name', 'contact_last_name', 'address_line_1', 'address_line_2', 'contact_phone', 'state', 'city', 'postcode', 'capacity', 'notification_message', 'is_invoice_notification', 'created_at', 'updated_at', 'is_deleted'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
    
}
