<?php

Route::group(['module' => 'Assistant', 'middleware' => ['api'], 'namespace' => 'App\Modules\Assistant\Controllers'], function() {

    Route::resource('assistant', 'AssistantController');

});
