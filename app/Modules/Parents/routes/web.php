<?php

Route::group(['module' => 'Parents', 'middleware' => ['web'], 'namespace' => 'App\Modules\Parents\Controllers'], function() {

    Route::resource('Parents', 'ParentsController');

});
