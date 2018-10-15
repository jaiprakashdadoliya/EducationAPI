<?php

Route::group(['module' => 'Assistant', 'middleware' => ['web'], 'namespace' => 'App\Modules\Assistant\Controllers'], function() {

    Route::resource('assistant', 'AssistantController');

});
