<?php

Route::group(['module' => 'CSVImportExport', 'middleware' => ['web'], 'namespace' => 'App\Modules\CSVImportExport\Controllers'], function() {

    Route::resource('CSVImportExport', 'CSVImportExportController');

});
