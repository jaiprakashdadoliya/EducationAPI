<?php

Route::group(['module' => 'Parents', 'middleware' => ['api'], 'namespace' => 'App\Modules\Parents\Controllers'], function() {

    Route::resource('Parents', 'ParentsController');

});
