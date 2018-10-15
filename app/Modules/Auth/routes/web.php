<?php

Route::group(['module' => 'Auth', 'middleware' => ['web'], 'namespace' => 'App\Modules\Auth\Controllers'], function() {

    Route::resource('auth', 'AuthController');

});
