<?php

Route::group(['module' => 'Admin', 'middleware' => ['web'], 'namespace' => 'App\Modules\Admin\Controllers'], function() {

    Route::resource('admin', 'AdminController');

});
