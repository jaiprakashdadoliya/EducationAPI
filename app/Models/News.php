<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * News
 *
 * @subpackage             News
 * @category               Model
 * @author                 soujany  <gaur.soujany@fxbytes.com>
 * @DateOfCreation         19 July 2018
 * @ShortDescription       This model connect with the news table 
 */
class News extends Model
{
    /** @var String $primaryKey
     *  This protected member contains talbe primary key
     */
    protected $primaryKey = 'news_id';
    protected $table = 'news'; 

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'school_id', 'title', 'image', 'description', 'news_date', 'status', 'resource_type', 'user_agent', 'ip_address', 'user_agent', 'is_deleted', 'created_by', 'updated_by', 'created_at', 'updated_at', 'resource_type'
    ];
}