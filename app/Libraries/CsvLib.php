<?php

namespace App\Libraries;
use Illuminate\Http\Request;
use Validator;
use Config;
use Excel;
use Schema;
use DB;

/**
 * CsvLib Class
 *
 * @package                Laravel Base Setup
 * @subpackage             CsvLib
 * @category               Library
 * @DateOfCreation         05 Apr 2018
 * @ShortDescription       This class is responsible for import and export in CSV and EXCEL.

 */
class CsvLib {
  
    /**
     * Create a new library instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
  
   /**
    * @DateOfCreation        05 Apr 2018
    * @ShortDescription      This function is responsible to import data from file .
    * @param                 String $fileToImport
    * @return                Array  ( All imported data ) 
    */
    public function importData($fileToImport)
    {   
        if(!empty($fileToImport)){
            $path = $fileToImport->getRealPath();
            $data = Excel::load($path)->toArray();
            if(count($data)>0){
                return ['code' => '1000','message' => trans('messages.1001'),'result' => $data];
            }else{
                return ['code' => '5000', 'message' => trans('messages.5001'), 'result' => []];
            }
        }else{
            return ['code' => '5000', 'message' => trans('messages.5004'), 'result' => []];
        }
    }

   /**
    * @DateOfCreation        28 June 2018
    * @ShortDescription      This function is responsible to import data from saved file .
    * @param                 String $path ( please provide full path till file )
    * @return                Array  ( All imported data ) 
    */
    public function importDataFromSaved($path)
    {   
        if(!empty($path)){
            
            $data = Excel::load(storage_path($path))->toArray();
            if(count($data)>0){
                return ['code' => '1000','message' => __('messages.1001'),'result' => $data];
            }else{
                return ['code' => '5000', 'message' => __('messages.5001'), 'result' => []];
            }
            
        }else{
            return ['code' => '5000', 'message' => __('messages.5004'), 'result' => []];
        }
    
    }

   /**
    * @DateOfCreation        05 July 2018
    * @ShortDescription      This function is export data in given format and download file.
    * @LoongDescription      Extra info parameter is optional if it is empty or the value of 
    *                        sheet title and name is empty we will assign the downloadFileName 
    *                        parameter to both places. Columns and Header must be same in  length.   
    * @param                 Array $data   - Format $data = [
    *                                                            ['C1','C2','C3'],
    *                                                            ['C11','C12','C13'],
    *                                                            ['C21','C22','C23']
    *                                                        ]                          
    *                         Array $headers 
    *                         String $downloadFileName  - 'Format supported CSV ,XLS, XLSX'
    *                         String $downloadType
    *                         Array $extraInfo (
    *                            String sheetTitle
    *                            String sheetName
    *                         )  (This parameter is optional) 
    * @return                Download file
    */
    public function     exportBlankData($data,$headers,$downloadFileName,$downloadType,$extraInfo = [])
    {
        if(!empty($extraInfo)){
            $sheetTitle = (!empty($extraInfo['sheetTitle']) ? $extraInfo['sheetTitle'] : $downloadFileName);
            $sheetName  = (!empty($extraInfo['sheetName']) ? $extraInfo['sheetName'] : $downloadFileName);
        }else{
            $sheetTitle = $downloadFileName;
            $sheetName = $downloadFileName;
        }
        $dataLength = count($headers);
       
            return Excel::create($downloadFileName, function($excel) use ($data,$headers,$sheetTitle,$sheetName,$downloadType,$dataLength) {
                $excel->setTitle($sheetTitle);
                $excel->sheet($sheetName, function($sheet) use ($data,$headers,$downloadType,$dataLength)
                {
                    $i = 'A';
                    $j = 1;
                    foreach ($headers as $value) {
                        $sheet->cell($i.$j, function($cell) use ($value,$dataLength){
                            $cell->setValue($value);
                        });
                        $i++;
                    }
                    if (!empty($data)) {
                        foreach ($data as $key => $value) {
                            $k= $key+2;$column = 'A';
                            for ($i=0; $i < $dataLength; $i++) { 
                                $sheet->cell($column.$k, $value[$i]);
                                $column++;
                            }
                        }
                    }
                })->download($downloadType);
            });

    }

   /**
    * @DateOfCreation        05 Apr 2018
    * @DateOfDeprecated      12 Apr 2018
    * @ShortDescription      This function is export data in given format and download file.
    * @LoongDescription      Extra info parameter is optional if it is empty or the value of 
                             sheet title and name is empty we will assign the downloadFileName 
                             parameter to both places. Columns and Header must be same in  length.   
    * @param                 Array $data   - Format $data = [
                                                                ['C1','C2','C3'],
                                                                ['C11','C12','C13'],
                                                                ['C21','C22','C23']
                                                            ]                          
                             Array $headers 
                             String $downloadFileName  - 'Format supported CSV ,XLS, XLSX'
                             String $downloadType
                             Array $extraInfo (
                                String sheetTitle
                                String sheetName
                             )  (This parameter is optional) 
    * @return                Download file
    */
    public function exportData($data,$headers,$downloadFileName,$downloadType,$extraInfo = [])
    {
        if(!empty($extraInfo)){
            $sheetTitle = (!empty($extraInfo['sheetTitle']) ? $extraInfo['sheetTitle'] : $downloadFileName);
            $sheetName  = (!empty($extraInfo['sheetName']) ? $extraInfo['sheetName'] : $downloadFileName);
        }else{
            $sheetTitle = $downloadFileName;
            $sheetName = $downloadFileName;
        }
        $dataLength = count($headers);
        if(empty($data)){
            return ['code' => '5000','message' => trans('messages.5015')];
        }
        if(empty($headers)){
            return ['code' => '5000','message' => trans('messages.5016')];
        }
        if(empty($downloadFileName)){
            return ['code' => '5000','message' => trans('messages.5017')];
        }
        if(empty($downloadType)){
            return ['code' => '5000','message' => trans('messages.5018')];
        }
        $verifyData = $this->checkData($data, $dataLength);
        if($verifyData){
            return Excel::create($downloadFileName, function($excel) use ($data,$headers,$sheetTitle,$sheetName,$downloadType,$dataLength) {
                $excel->setTitle($sheetTitle);
                $excel->sheet($sheetName, function($sheet) use ($data,$headers,$downloadType,$dataLength)
                {
                    $i = 'A';
                    $j = 1;
                    foreach ($headers as $value) {
                        $sheet->cell($i.$j, function($cell) use ($value,$dataLength){
                            $cell->setValue($value);
                        });
                        $i++;
                    }
                    if (!empty($data)) {
                        foreach ($data as $key => $value) {
                            $k= $key+2;$column = 'A';
                            for ($i=0; $i < $dataLength; $i++) { 
                                $sheet->cell($column.$k, $value[$i]);
                                $column++;
                            }
                        }
                    }
                })->download($downloadType);
            });
        }else{
            return ['code' => '5000','message' => trans('messages.5019')];
        }
    }


    /**
     * @DateOfCreation        2 July 2018
     * @ShortDescription      This function is responsible for validating CSV file.
     * @param                 Array $data This contains file object.
     * @return                array
     */
    public function csvValidator(array $data)
    {
        $error = false;
        $errors = [];
        $validator = Validator::make($data, [
            'import' => 'required|mimes:csv,txt|max:4096' // 4MB
        ]);
        if($validator->fails()){
            $error = true;
            $errors = $validator->errors();
        }
        return ["error" => $error,"errors" => $errors];
    }

    /**
     * @DateOfCreation        2 July 2018
     * @ShortDescription      This function is responsible to check data in CSV file.
     * @param                 Array $data This contains file object.
     * @return                array
     */
    public function csvDataCheck($path, $csvValidateData)
    {
        // Check table.
        $validateTable = $this->checkTable($csvValidateData);
        if (!empty($validateTable)) {
            $error = true;
            $errors['error_data'] = '0|import_wrong_table|'.$validateTable;
            return ["error" => $error,"errors" => $errors];
        }

        // Check column.
        $validateColumn = $this->checkColumn($csvValidateData);
        if (!empty($validateColumn)) {
            $error = true;
            $errors['error_data'] = '0|import_wrong_column|'.$validateColumn;
            return ["error" => $error,"errors" => $errors];
        }

        // Fetch data from excel.
        $data = Excel::load(storage_path($path), function($reader) {
            $reader->noHeading();
        })->toArray();
        $error = false;
        $errors = [];
        $delete_count = 0;
        $update_count = 0;
        $insert_count = 0;

        // Remove blank rows.
        if (!empty($data)) {
            $emptyRowArray = array();
            $count = count($data[0]);
            foreach ($data as $mainkey => $rows) {
                $countBlank = 0;
                if ($mainkey > 0) {
                    foreach ($rows as $key => $dataRow) {
                        if($dataRow == null) {
                            $countBlank++;
                        }
                    }
                }
                if ($count == $countBlank) {
                    $emptyRowArray[] = $mainkey+1;
                }
            }
            if (!empty($emptyRowArray)) {
                $error = true;
                $emptyRowArray = implode(',',  $emptyRowArray);
                $errors['error_data']['blank_row'][] =  $emptyRowArray;
                $errors['error_data']['blank_row'][] = 'import_empty_row_message|0';
                return ["error" => $error,"errors" => $errors];
            }
        }
        // Csv heading check.
        $data = array_filter($data);
        $headingData = reset($data);
        $indexMobile = '';
        $indexEmail = '';
        if (in_array('Contact', $headingData)) {
            $indexMobile = array_search('Contact', $headingData);
        }
        if (in_array('Email', $headingData)) {
            $indexEmail = array_search('Email', $headingData);
        }
        if (!empty($headingData)) {
            $parentTableName = key($csvValidateData[0]);
            foreach ($csvValidateData as $key => $tabledata) {
                $tablename = key($tabledata);
                foreach ($tabledata as $tablekey => $column) {
                    foreach ($column as $columnkey => $labelData) {
                        $columnArr[] = $labelData['Column'].'|'.$tablename;
                        $labelArray[] = $labelData['Label'];
                        if (array_key_exists("Required",$labelData)) {
                            $conditionArray[$labelData['Column']][] = 'required';
                        }
                        if (array_key_exists("Unique",$labelData)) {
                            $conditionArray[$labelData['Column']][] = 'unique';
                        }
                        if (array_key_exists("Max",$labelData)) {
                            $conditionArray[$labelData['Column']]['max'] = $labelData['Max'];
                        }
                        if (array_key_exists("Min",$labelData)) {
                            $conditionArray[$labelData['Column']]['min'] = $labelData['Min'];
                        }
                        if (array_key_exists("DataType",$labelData)) {
                            $conditionArray[$labelData['Column']]['DataType'] = $labelData['DataType'];
                        }
                        if (array_key_exists("ForeignKeyCheck",$labelData)) {
                            $conditionArray[$labelData['Column']]['ForeignKeyCheck'] = $labelData['ForeignKeyCheck'];
                        }
                        if (array_key_exists("typeCheck",$labelData)) {
                            $conditionArray[$labelData['Column']]['typeCheck'] = $labelData['typeCheck'];
                        }
                        if (array_key_exists("adminCheck",$labelData)) {
                            $conditionArray[$labelData['Column']]['adminCheck'] = $labelData['adminCheck'];
                        }
                        if (array_key_exists("preventUniqueCheck",$labelData)) {
                            $conditionArray[$labelData['Column']]['preventUniqueCheck'] = $labelData['preventUniqueCheck'];
                        }
                    }
                }
            }
            $conditionArray['is_deleted'] = array('string');
            // Check heading count.
            if (count($labelArray) !== count($headingData)) {
                $error = true;
                $errors['error_data'] = '0|import_heading_count|0';
                return ["error" => $error,"errors" => $errors];
            }

            // Check heading name.
            $heading = array();
            foreach ($labelArray as $key => $column) {
                    $column = trim($column);
                if (!in_array($column, $headingData)) {
                    $heading[] = $column;
                }
            }

            if (!empty($heading)) {
                $error = true;
                $headingName = implode(', ', $heading);
                $errors['error_data'] = '0|import_heading_name_message|'.$headingName;
                return ["error" => $error,"errors" => $errors];
            }

            // Check heading sequence.
            $keys = array_keys($labelArray, $headingData[0]);
            if (!empty($keys)) {
                foreach ($keys as $k) {
                    $tt =  array_slice($labelArray, $k, count($headingData));
                    if (array_slice($labelArray, $k, count($headingData)) != $headingData) {
                        $error = true;
                        $errors['error_data'] = '0|import_heading_sequence_message|0';
                    }
                }
                if ($error) {
                    return ["error" => $error,"errors" => $errors];
                }
            } else {
                $error = true;
                $errors['error_data'] = '0|import_heading_sequence_message|0';
                return ["error" => $error,"errors" => $errors];
            }

            // Check unique Reference id in rows.
            $data = array_values($data);
            $id_array = array();
            $id_duplicate_array = array();
            $emailArray = array();
            $emailDuplicateArray = array();
            $mobileArray = array();
            $mobileDuplicateArray = array();
            $unique_check_prevent = 0;
            foreach ($conditionArray as $key => $condition) {
                if (array_key_exists('preventUniqueCheck',$condition)) {
                    $unique_check_prevent = 1;
                    break;
                }
            }
            foreach ($data as $key => $value) {
                if($key > 0) {
                    if($unique_check_prevent == 0){
                        $id = intval($value[0]);
                        if (!in_array($id, $id_array)) {
                            $id_array[] = $id;
                        } else {
                            $id_duplicate_array[] = $key+1;
                        }
                    }

                    if ($indexEmail!='') {
                        $email = $value[$indexEmail];
                        if (!in_array($email, $emailArray)) {
                            $emailArray[] = $email;
                        } else {
                            $emailDuplicateArray[] = $key+1;
                        }
                    }

                    if ($indexMobile!='') {
                        $mobile = $value[$indexMobile];
                        if (!in_array($mobile, $mobileArray)) {
                            $mobileArray[] = $mobile;
                        } else {
                            $mobileDuplicateArray[] = $key+1;
                        }
                    }
                }
            }
            if (!empty($id_duplicate_array)) {
                $error = true;
                $duplicateId = implode(',',$id_duplicate_array);
                $errors['error_data'] = '0|duplicate_record_message|'.$duplicateId;
                return ["error" => $error,"errors" => $errors];
            }
            if (!empty($emailDuplicateArray)) {
                $error = true;
                $duplicateEmail = implode(',',$emailDuplicateArray);
                $errors['error_data'] = '0|duplicate_email_record_message|'.$duplicateEmail;
                return ["error" => $error,"errors" => $errors];
            }
            if (!empty($mobileDuplicateArray)) {
                $error = true;
                $duplicateMobile = implode(',',$mobileDuplicateArray);
                $errors['error_data'] = '0|duplicate_mobile_record_message|'.$duplicateMobile;
                return ["error" => $error,"errors" => $errors];
            }
        }
        if(count($data) == 1) {
            $error = true;
            $errors['error_data'] = '0|import_blank_row_without_heading|0';
            return ["error" => $error,"errors" => $errors];
        }

        foreach ($data[0] as $value) {
            $typeCheck = explode(' ',$value);
            $type = end($typeCheck);
            if($type == 'Type')
            {
                $type = $value;
                break;
            }
        }
        foreach ($data as $mainkey => $item) {
            if ($mainkey > 0) {
                $validationArray = array();
                $validationMessageArray = array();
                foreach ($item as $key => $value) {
                    $validateHtml = '';
                    $validateMessage = array();
                    $columnArrayData = explode('|', $columnArr[$key]);
                    $columnName = $columnArrayData[0];
                    $columnTableName = $columnArrayData[1];
                    $typeListArr = Config::get('importConstants.type');
                    if(!empty($typeListArr)) {
                        if (in_array($type,$labelArray)) {
                            $typeIndex = array_search($type, $labelArray);
                            if ($key == $typeIndex) {
                                $value = strtolower($value);
                            }
                        }
                        if (in_array('Delete',$labelArray)) {
                            $typeIndex = array_search('Delete', $labelArray);                            
                            if ($key == $typeIndex) {
                                $value = strtolower($value);
                                $item[$key] = $value;
                            }
                        }
                    }
                    $dataValidationArray[$columnName] = $value;
                    if (array_key_exists('ForeignKeyCheck',$conditionArray[$columnName])) {

                        // Do not check validation foreign key id is empty in csv file.
                        if (isset($value) && !empty($value)) {
                            $tableName = explode('_',$columnName);
                            $validateHtml .="foreign_key_check:$tableName[0]|";
                            $validateMessage [$columnName.'.ForeignKeyCheck']= 'import_foreign_key_check_message';
                        }
                    }
                    if (array_key_exists('typeCheck',$conditionArray[$columnName])) {
                        // Do not check validation foreign key id is empty in csv file.
                        if (isset($value) && !empty($value)) {
                            $tableName     = explode('_',$columnName);
                            $data_validate = $conditionArray[$columnName]['typeCheck'];
                            $validateHtml .="type_check:$data_validate,$value|";
                            $validateMessage [$columnName.'.typeCheck']= 'import_foreign_key_type_check_message';
                        }
                    }

                    if (array_key_exists('adminCheck',$conditionArray[$columnName])) {

                        // Do not check validation foreign key id is empty in csv file.
                        if (isset($value) && !empty($value)) {
                            $tableName     = explode('_',$columnName);
                            $data_validate = $conditionArray[$columnName]['adminCheck'];
                            $validateHtml .="admin_check:$data_validate|";
                            $validateMessage [$columnName.'.adminCheck']= 'import_admin_reference_restrict_message';
                        }
                    }

                    if (in_array('required',$conditionArray[$columnName])) {
                        $validateHtml .='required|';
                        $validateMessage [$columnName.'.required']= 'import_reference_message';
                    }
                    if (in_array('string',$conditionArray[$columnName])) {
                        $validateHtml .='string|in:true,false';
                        $validateMessage [$columnName.'.string']= 'import_only_string_message';
                        $validateMessage [$columnName.'.in']= 'import_delete_true_false_message';
                    }
                    if (array_key_exists('max',$conditionArray[$columnName])) {
                        $digits = 'numeric|digits:10|';
                        $validateHtml .= $digits;
                        $validateMessage [$columnName.'.digits']= 'import_digits_format';
                    }

                    if (array_key_exists('DataType',$conditionArray[$columnName])) {
                        if(is_array($conditionArray[$columnName]['DataType'])) {
                            if (array_key_exists('enum',$conditionArray[$columnName]['DataType'])) {
                               
                                if ($columnTableName == 'users' && in_array('Employee Type', $labelArray)) {
                                    $typeCheck = 'in:assistant,driver';
                                    $validateHtml .= $typeCheck;
                                    $validateMessage[$columnName.'.in']= 'import_type_check';
                                }

                                if ($columnTableName == 'vehicle' && in_array('Vehicle Type', $labelArray)) {
                                    $typeCheck = 'in:bus,mini-bus,van';
                                    $validateHtml .= $typeCheck;
                                    $validateMessage[$columnName.'.in']= 'import_type_check';
                                }
                            }
                        }
                    }
                    if (in_array('email',$conditionArray[$columnName])) {
                        $email = 'email|';
                        $validateHtml .= $email;
                        $validateMessage [$columnName.'.email']= 'import_wrong_format_email';
                    }

                    if (in_array('date',$conditionArray[$columnName])) {
                        $date = 'date_format:Y-m-d|';
                        $validateHtml .= $date;
                        $validateMessage [$columnName.'.date_format']= 'import_wrong_format_date';
                    }

                    if (in_array('time',$conditionArray[$columnName])) {
                        $time = 'date_format:H:i|';
                        $validateHtml .= $time;
                        $validateMessage [$columnName.'.date_format']= 'import_wrong_format_time';
                    }
                    
                    if (in_array('unique',$conditionArray[$columnName])) {
                        $unique = 'unique:'.$columnTableName.','.$columnName.','.$item[0].','.mb_substr($columnTableName, 0, -1).'_reference';
                        $validateHtml .= $unique;
                        $validateMessage [$columnName.'.unique']= 'import_unique';
                    }
                    $validateHtml = rtrim($validateHtml,'|');
                    $validationArray[$columnName] = $validateHtml;
                    foreach ($validateMessage as $key => $message) {
                        $validationMessageArray[$key] = $message;
                    }
                }

                $validator = Validator::make($dataValidationArray, $validationArray, $validationMessageArray);
                if ($validator->fails()) {
                    $error = true;
                    $errors = $validator->errors();
                    foreach ($columnArr as $key => $column) {
                        $columnArrayData = explode('|', $column);
                        $columnName = $columnArrayData[0];
                        if ($errors->first($columnName)) {
                            $error_message['error_data']['blank_row'][] =  $mainkey+1;
                            $error_message['error_data']['blank_row'][] = $errors->first($columnName)."|$columnName";
                        }
                    }
                }
                $reference = mb_substr($parentTableName, 0, -1);
                $tableData = DB::select(DB::raw("SELECT ".$reference."_reference FROM $parentTableName WHERE ".$reference."_reference = :reference_id AND is_deleted = :is_deleted" ), array('reference_id' => $item[0], 'is_deleted' => 0));
                $deletedAt = array_pop($item);
                if($deletedAt === 'true'){
                    $delete_count++;
                }else{
                    if(count($tableData) > 0){
                        $update_count++;
                    }else{
                        $insert_count++;
                    }
                }
            }
        }
        if ($error) {
            return ["error" => $error,"errors" => $error_message];
        } else {
            $count_array['insert_count'] = $insert_count;
            $count_array['update_count'] = $update_count;
            $count_array['delete_count'] = $delete_count;
            return ["error" => $error, "success_count" => $count_array];
        }
    }

    public function checkTable($csvValidateData)
    { 
        // Check table name.
        $tableName = key($csvValidateData);
        $tableNameArr = Config::get('csvColumnConstant.tables');
        $wrongTableArr =array();
        foreach ($csvValidateData as $key => $data) {
            $table = key($data);
            if (!in_array($table, $tableNameArr)) {
                $wrongTableArr[] = $table;
            }
        }
        $wrongTableArr = implode(',', $wrongTableArr);
        return $wrongTableArr;
    }

    public function checkColumn($csvValidateData) 
    {
        // Check column name.
        $wrongColumnArray = array();
        foreach ($csvValidateData as $tablekey => $tableArr) {
            $table = key($tableArr);
            foreach ($tableArr[$table] as $columnkey => $columnArr) {
                if (!Schema::hasColumn($table, $columnArr['Column'])) {
                    $wrongColumnArray[] = $columnArr['Column'];
                }
            }
        }

        $wrongColumnArray = implode(',', $wrongColumnArray);
        return $wrongColumnArray;
    }
}