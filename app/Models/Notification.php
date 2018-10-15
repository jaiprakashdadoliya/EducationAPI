<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Notification
 *
 * @subpackage             Notification
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         20 July 2018
 * @ShortDescription       This model connect with the notification table 
 */
class Notification extends Model
{
	/** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'notification_id';
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id', 'is_notification', 'is_reminder', 'created_by', 'updated_by', 'created_at', 'updated_at', 'resource_type'
    ];
}