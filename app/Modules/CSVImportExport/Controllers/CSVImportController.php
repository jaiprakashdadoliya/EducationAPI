<?php

namespace App\Modules\CSVImportExport\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Libraries\CsvLib;
use App\Libraries\FileLib;
use App\Traits\RestApi;
use App\Models\User;
use App\Libraries\SecurityLib;
use Config;
use Excel;
use DB;

class CSVImportController extends Controller
{
    use RestApi;

    /** @var                Array $http_codes
     *  @ShortDescription   This protected member contains http status Codes
     */
    protected $http_codes = [];

    public $successStatus = 200;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Json array of http codes.
        $this->http_codes = $this->http_status_codes();

        // Init CSV Library object
        $this->csvLibObj  = new CsvLib();

        // Init File Library object
        $this->FileLib    = new FileLib();

        // Init security library object
        $this->securityLibObj = new SecurityLib();  
    }

    /**
     * Validating import file contents.
     *
     * @return \Illuminate\Http\Response
     */
    public function import_validation(Request $request)
    {
        $requestData = $request->all();
        $jsonData = Config::get('importConstants.'.$requestData['importData']);

        // Check csv format.
        $validate = $this->csvLibObj->csvValidator($requestData);
        if ($validate["error"]) {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                $validate['errors'],
                trans('CSVImportExport::import.import_csv_file_check'),
                $this->http_codes['HTTP_OK']
            );
        }

        // Check json data
        if (!empty($jsonData)) {
            $csvValidateData = json_decode($jsonData, true);
        } else {
            return $this->resultResponse(
                Config::get('restresponsecode.ERROR'),
                [],
                $validate['errors'],
                trans('CSVImportExport::import.import_request_data'),
                $this->http_codes['HTTP_OK']
            );
        }

        // Store Csv file.
        $destination = 'csv/';
        $fileUpload = $this->FileLib->fileUpload($requestData['import'], $destination);
        
        // Csv file record validation.
        $import = $this->csvLibObj->csvDataCheck($destination.$fileUpload['uploaded_file'], $csvValidateData);
        if($import['error']) {
            if (!empty($import['errors']['error_data']['blank_row'])) {
                $errorCount = count($import['errors']['error_data']['blank_row']);
                for ($i = 0; $i < $errorCount; $i++) {
                    if ($i == 0) {
                        $errorMessageArr = explode('|',$import['errors']['error_data']['blank_row'][$i+1]);
                        $errorMessageData[$i] = 'Line number: '.$import['errors']['error_data']['blank_row'][$i].' '.$errorMessageArr[1].' '.trans('CSVImportExport::import.'.$errorMessageArr[0]);
                    } else {
                        if($errorCount < $i*2+1)
                        {
                            break;
                        }
                        $errorMessageArr = explode('|',$import['errors']['error_data']['blank_row'][$i*2+1]);
                        $errorMessageData[$i] = 'Line number: '.$import['errors']['error_data']['blank_row'][$i*2].' '.$errorMessageArr[1].' '.trans('CSVImportExport::import.'.$errorMessageArr[0]);
                    }
                }
                $error_message = implode(', ', $errorMessageData);
                return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    $error_message,
                    $this->http_codes['HTTP_OK']
                );
            } else {
                $errorMessageData = explode('|', $import['errors']['error_data']);
                if (empty($errorMessageData[2])) {
                    $error_data = '';
                } else {
                    $error_data = $errorMessageData[2];
                }
                return $this->echoResponse(
                    Config::get('restresponsecode.ERROR'),
                    [],
                    [],
                    trans('CSVImportExport::import.'.$errorMessageData[1]).' '.$error_data,
                    $this->http_codes['HTTP_OK']
                );
            }
        } else {
            $success[] = $import['success_count'];
            return $this->echoResponse(
                Config::get('restresponsecode.SUCCESS'),
                $success,
                [],
                trans('CSVImportExport::import.import_list_successfull'),
                $this->http_codes['HTTP_OK']
            );
        }
    }

    /**
     * @DateOfCreation      05 July 2018
     * @ShortDescription    Save import csv data.
     * @param               Object $request this contains full request.
     * @return              Array
     */
    public function import_save(Request $request)
    {
        $requestDefault = array();
        $user_id = Auth::id();
        $requestDefault['school_id'] = User::find($user_id)->school_id;

        $requestData = $request->all();
        $jsonKey = $requestData['importSave'];
        if($jsonKey == 'parent_save'){
            $requestDefault['user_type'] = 'parent';
        }
        $constantData = Config::get('importConstants.'.$jsonKey);
        $tableCount = count($constantData);
        if (count($constantData) > 1) {
            $arrayFirst = explode(',',$constantData[0]);
            $countFirstArray = count($arrayFirst);
            $arraySecond = explode(',',$constantData[1]);
            $tableData = array_merge($arrayFirst, $arraySecond);
            $tableNameSplit[] = explode("_", $arrayFirst[0]);
            $tableNameSplit[] = explode("_", $arraySecond[0]);
            foreach ($tableNameSplit as $key => $value) {
                $count = count($value);
                if ($count > 2) {
                    unset($value[$count-1]);
                    $tableName[$key] = implode('_',$value);
                } else {
                    $tableName[$key] = $value[0];
                }
            }
        } else {
            $tableData = explode(',',$constantData[0]);
            $tableNameSplit = explode("_", $tableData[0]);
            $tableNameLength = count($tableNameSplit);
            if ($tableNameLength > 2) {
                unset($tableNameSplit[$tableNameLength-1]);
                $tableName[] = implode('_',$tableNameSplit);
            } else {
                $tableName[] = $tableNameSplit[0];
            }
        }
        array_push($tableData,'is_deleted');
        $destination = 'csv/';
        $fileUpload = $this->FileLib->fileUpload($requestData['import'], $destination);
        $csvData = $this->csvLibObj->importDataFromSaved($destination.$fileUpload['uploaded_file']);
        $csvData['result'] = array_map('array_values', $csvData['result']);
        foreach ($csvData['result'] as $key => $value) {
            $csvData['result'][$key] = array_combine($tableData, $value);
        }
        $user_agent = $request->server('HTTP_USER_AGENT');
        $requestDefault['updated_by'] = $user_id;
        $requestDefault['created_by'] = $user_id;
        $requestDefault['resource_type'] = 'web';
        $requestDefault['user_agent'] = $user_agent;
        $requestDefault['ip_address'] = $request->ip();    
        $parentTableReferenceKey = $tableData[0];
        foreach ($csvData['result'] as $key => $column) {
            $firstTable = $tableName[0].'s';
            $referenceId= $tableName[0]."_reference";
            $tableData = DB::table("$firstTable AS t")
                            ->select("t.$referenceId", "t.is_deleted")
                            ->where($referenceId,'=',$column[$referenceId])
                            ->where('school_id','=', $requestDefault['school_id'])
                            ->where('is_deleted','=',0)
                            ->first();
            if ($tableData) {
                if ($tableCount == 2) {
                    if (strtolower($column['is_deleted']) === 'true') {
                        $deleteArray['is_deleted'] = 1;
                        $deleteArray['updated_at'] = date('Y-m-d H:i:s');
                        $firstTable = $tableName[0];

                        // Delete first table.
                        DB::table($tableName.'s')
                          ->where($firstTable.'_reference', $column[$firstTable.'_reference'])
                          ->where('school_id','=',$requestDefault['school_id'])
                          ->update($deleteArray);

                        // Delete second table.
                        $secondTable = $tableName[1];
                        DB::table($secondTable.'s')
                          ->where($secondTable.'_reference', $column[$secondTable.'_reference'])
                          ->where('school_id','=',$requestDefault['school_id'])
                          ->update($deleteArray);
                    } else {
                        
                        // Update first table.
                        $requestDefault['updated_at'] = date('Y-m-d H:i:s');
                        $firstTable = $tableName[0];
                        $firstTableData = array_slice($column,0,$countFirstArray);
                        $index = 0;
                        foreach ($firstTableData as $key => $val) {
                            if ($index > 0) {
                                $pos = strpos($key, '_');
                                if ($pos) {
                                    $columnName= explode('_',$key);
                                    if ($columnName[1] == 'reference') {
                                        $table = $columnName[0].'s';
                                        $referenceId= $columnName[0]."_reference";
                                        $id = DB::table("$table AS t")
                                                        ->select("t.".$table."_id as value")
                                                        ->where($referenceId,'=',$column[$referenceId])
                                                        ->where('school_id','=',$requestDefault['school_id'])
                                                        ->first();
                                    $firstTableData[$table.'_id'] = $id->value;
                                    unset($firstTableData[$key]);
                                    }
                                }
                            }
                            $index++;
                        }
                        $data = array_merge($firstTableData, $requestDefault);
                        if (strtolower($column['is_deleted']) == 'true') {
                            $column['is_deleted'] = 1;
                        }

                        if (strtolower($column['is_deleted']) == 'false' || empty($column['is_deleted'])) {
                            $column['is_deleted'] = 0;
                        }
                        $data['is_deleted'] = $column['is_deleted'];
                        DB::table($firstTable.'s')
                          ->where($firstTable.'_reference', $column[$firstTable.'_reference'])
                          ->where('school_id','=',$requestDefault['school_id'])
                          ->update($data);

                        // Update second table.
                        $secondTable = $tableName[1].'s';
                        $childTableReferenceKey = $tableName[1].'_reference';
                        $secondTableData = array_slice($column,$countFirstArray);
                        unset($secondTableData['is_deleted']);
                        $index = 0;
                        foreach ($secondTableData as $key => $val) {
                            if ($index > 0) {
                                $pos = strpos($key, '_');
                                if ($pos) {
                                    $columnName= explode('_',$key);
                                    if ($columnName[1] == 'reference') {
                                        $table = $columnName[0].'s';
                                        $referenceId= $columnName[0]."_reference";
                                        $id = DB::table("$table AS t")
                                                        ->select("t.".$table."_id as value")
                                                        ->where($referenceId,'=',$column[$referenceId])
                                                        ->where('school_id','=',$requestDefault['school_id'])
                                                        ->first();
                                        $secondTableData[$table.'_id'] = $id->value;
                                        unset($secondTableData[$key]);
                                    }
                                }
                            }
                            $index++;
                        }
                        $data = array_merge($secondTableData, $requestDefault);
                        if (substr($secondTable, -1) == 's')
                        {
                            $secondTable = substr($secondTable, 0, -1);
                        }
                        DB::table($secondTable.'s')
                          ->where($secondTable.'_reference', $column[$secondTable.'_reference'])
                          ->where('school_id','=',$requestDefault['school_id'])
                          ->update($data);
                    }
                } else {
                    if (strtolower($column['is_deleted']) === 'true') {
                        $deleteArray['is_deleted'] = 1;
                        $deleteArray['updated_at'] = date('Y-m-d H:i:s');

                        // Delete
                        DB::table($tableName[0].'s')
                          ->where($tableName[0].'_reference', $column[$tableName[0].'_reference'])
                          ->where('school_id','=',$requestDefault['school_id'])
                          ->update($deleteArray);
                    } else {
                        // Update first table.
                        $requestDefault['updated_at'] = date('Y-m-d H:i:s');
                        $index = 0;
                        foreach ($column as $key => $val) {
                            if ($index > 0) {
                                $pos = strpos($key, '_');
                                if ($pos) {
                                    $columnName= explode('_',$key);
                                    if ($columnName[1] == 'reference') {
                                        $table = $columnName[0].'s';
                                        $referenceId= $columnName[0]."_reference";
                                        $id = DB::table("$table AS t")
                                                        ->select("t.".$table."_id as value")
                                                        ->where($referenceId,'=',$column[$referenceId])
                                                        ->where('school_id','=',$requestDefault['school_id'])
                                                        ->first();
                                    $column[$table.'_id'] = $id->value;
                                    unset($column[$key]);
                                    }
                                }
                            }
                            $index++;
                        }
                        if (strtolower($column['is_deleted']) == 'true') {
                            $column['is_deleted'] = 1;
                        }

                        if (strtolower($column['is_deleted']) == 'false' || empty($column['is_deleted'])) {
                            $column['is_deleted'] = 0;
                        }

                        $data = array_merge($column, $requestDefault);
                        DB::table($tableName[0].'s')
                          ->where($tableName[0].'_reference', $column[$tableName[0].'_reference'])
                          ->where('school_id','=',$requestDefault['school_id'])
                          ->update($data);
                    }
                }
            } else {
                // Insert
                $requestDefault['created_at'] = date('Y-m-d H:i:s');
                $requestDefault['updated_at'] = date('Y-m-d H:i:s');
                $requestDefault['is_deleted'] = 0;
                if ($tableCount == 2) {
                    // First table insert
                    $firstTable = $tableName[0].'s';
                    $firstTableData = array_slice($column,0,$countFirstArray);
                    $tableReferenceId = $tableName[0].'_reference';
                    //$tableColumns = DB::select(DB::raw("SELECT column_name FROM information_schema.columns WHERE table_name='$firstTable'; "));
                    $tableColumnArray = array();
                    $index = 0;
                    foreach ($firstTableData as $key => $val) {
                        if ($index > 0) {
                            $pos = strpos($key, '_');
                            if ($pos) {
                                $columnName= explode('_',$key);
                                if ($columnName[1] == 'reference') {
                                    $table = $columnName[0].'s';
                                    $referenceId= $columnName[0]."_reference";
                                     $id = DB::table("$table AS t")
                                                    ->select("t.".$table."_id as value")
                                                    ->where($referenceId,'=',$column[$referenceId])
                                                    ->where('school_id','=',$requestDefault['school_id'])
                                                    ->first();
                                $firstTableData[$table.'_id'] = $id->value;
                                unset($firstTableData[$key]);
                                }
                            }
                        }
                        $index++;
                    }
                    $data = array_merge($firstTableData, $requestDefault);
                    if (empty($column['is_deleted'])) {
                        $column['is_deleted'] = 0;
                    }
                    $data['is_deleted'] = $column['is_deleted'];
                    $school_id = $data['school_id'];
                    DB::table($firstTable)->insert($data);
                    $lastInsertedId = app('db')->getPdo()->lastInsertId();

                    // Second table insert
                    $secondTable = $tableName[1].'s';
                    $tableReferenceId = $tableName[1].'_reference';
                    //$tableColumns = DB::select(DB::raw("SELECT column_name FROM information_schema.columns WHERE table_name='$secondTable'; "));
                    $secondTableData = array_slice($column,$countFirstArray);
                    $secondTableData['school_id'] = $school_id;
                    unset($secondTableData['is_deleted']);
                    if (substr($firstTable, -1) == 's')
                    {
                        $firstTable = substr($firstTable, 0, -1);
                    }
                    $secondTableData[$firstTable.'_id'] = $lastInsertedId;
                    $index = 0;
                    foreach ($secondTableData as $key => $val) {
                        if ($index > 0) {
                            $pos = strpos($key, '_');
                            if ($pos) {
                                $columnName= explode('_',$key);
                                if ($columnName[1] == 'reference') {
                                    $table = $columnName[0].'s';
                                    $referenceId= $columnName[0]."_reference";
                                    $id = DB::table("$table AS t")
                                                    ->select("t.".$table."_id as value")
                                                    ->where($referenceId,'=',$column[$referenceId])
                                                    ->where('school_id','=',$requestDefault['school_id'])
                                                    ->first();
                                $secondTableData[$table.'_id'] = $id->value;
                                unset($secondTableData[$key]);
                                }
                            }
                        }
                        $index++;
                    }
                    $tableColumnArray = array();
                    $data = array_merge($secondTableData, $requestDefault);
                    DB::table($secondTable)->insert($data);
                } else {
                    $table_name = $tableName[0].'s';
                    $requestDefault['created_at'] = date('Y-m-d H:i:s');
                    $requestDefault['updated_at'] = date('Y-m-d H:i:s');
                    $requestDefault['is_deleted'] = 0;
                    $tableColumns = DB::select(DB::raw("SELECT column_name FROM information_schema.columns WHERE table_name='$table_name'; "));
                    $tableColumnArray = array();
                    $index = 0;
                    foreach ($column as $key => $val) {
                        if ($index > 0) {
                            $pos = strpos($key, '_');
                            if ($pos) {
                                $columnName= explode('_',$key);
                                if ($columnName[1] == 'reference') {
                                    $table = $columnName[0].'s';
                                    $tableId = $columnName[0];
                                    $referenceId= $columnName[0]."_reference";
                                    
                                    $id = DB::table("$table AS t")
                                                    ->select("t.".$tableId."_id as value")
                                                    ->where($referenceId,'=',$column[$referenceId])
                                                    ->where('school_id','=',$requestDefault['school_id'])
                                                    ->first();
                                $column[$table.'_id'] = $id->value;
                                unset($column[$key]);
                                }
                            }
                        }
                        $index++;
                    }
                    $data = array_merge($column, $requestDefault);
                    DB::table($table_name)->insert($data);
                }
            }
        }
        return $this->resultResponse(
            Config::get('restresponsecode.SUCCESS'),
            [],
            [],
            trans('CSVImportExport::import.import_detail_successfull'),
            $this->http_codes['HTTP_OK']
        );
    }

    /**
     * Export file contents.
     *
     * @return \Illuminate\Http\Response
     */
    public function export_file($user_id, $exportName)
    {
        //Decrypt user_id
        $user_id = $this->securityLibObj->decrypt(base64_decode($user_id));
        $school_id = User::find($user_id)->school_id;
        $constantData = Config::get('importConstants.'.$exportName);
        $headers = $constantData['header'];
        
        $extraInfo = [
            'sheetTitle' => $exportName,
            'sheetName'  => $exportName
        ];
        
        $headerArray =array();
        $downloadFileName = $exportName; 
        $downloadType = "csv";

        $orderbyColumn = key($constantData['orderby']);
        $orderby = array_shift($constantData['orderby']);
      
        $query = DB::table($constantData['table'])
                    ->select($constantData['column']);
        if($constantData['joinA']){
            $secondTable = explode(',', $constantData['joinA']);
            $secondColumn = explode('-', $secondTable[1]);
            $thirdColumn = explode('-', $secondTable[3]);
            $query->join($secondTable[0],$secondColumn[0].".".$secondColumn[1],$secondTable[2],$thirdColumn[0].".".$thirdColumn[1]);
        }

        if($constantData['joinB']){
            $secondTable = explode(',', $constantData['joinB']);
            $secondColumn = explode('-', $secondTable[1]);
            $thirdColumn = explode('-', $secondTable[3]);
            
            $query->join($secondTable[0],$secondColumn[0].".".$secondColumn[1],$secondTable[2],$thirdColumn[0].".".$thirdColumn[1]);            
        }

        $query->where($constantData['whereA'], "=", $school_id);
        foreach ($constantData['whereB'] as $key => $value) {
            $query->where($value, "=", 0);    
        }
        //$query->where($constantData['whereB'], "=", 0);
        
        if(!empty($constantData['whereIn'])){
            $columName =  key($constantData['whereIn']);
            $query->whereIn($columName, $constantData['whereIn'][$columName]);
        }
        $query->orderby($orderbyColumn, $orderby);
        $data = $query->toSql();
        $data = $query->get()->toArray();
        if(!empty($data)){
            $i = 0;
            foreach ($data as $key => $value) {
                foreach ($value as $key => $value1) {

                    $headerArray[$i][] = $value1;    
                    if($key == 'Delete' && $value1 != ''){
                        $headerArray[$i][] =   TRUE;
                    }
                }
                $i ++;
            }
        }
       return $this->csvLibObj->exportBlankData($headerArray, $headers, $downloadFileName, $downloadType, $extraInfo);
    }
}