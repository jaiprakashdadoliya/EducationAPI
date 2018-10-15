<?php

Route::group(['module' => 'CSVImportExport', 'middleware' => ['api'], 'namespace' => 'App\Modules\CSVImportExport\Controllers'], function() {

    Route::resource('CSVImportExport', 'CSVImportExportController');

});
