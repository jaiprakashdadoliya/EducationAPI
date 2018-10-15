<?php
namespace App\Libraries;
use Illuminate\Http\Request;

/**
 * DateTimeLib Class
 *
 * @package                Laravel Base Setup
 * @subpackage             DateTimeLib
 * @category               Library
 * @DateOfCreation         05 Apr 2018
 * @ShortDescription       This Library is responsible for all security functions
 */
class DateTimeLib {

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible to check the date is valid or not 
    * @param                 Date $date ( date you need to check )
    *                        String $format ( Current date format you are using in date parameter ) 
    * @return                Array with status, message and result() 
    */
    public function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $dateTimeObj = new \DateTime();
        $result = $dateTimeObj->createFromFormat($format, $date);
        if($result){
            return ['code' => '1000','message' => __('messages.1016'),'result' => $result->format($format)];
        }else{
            return ['code' => '5000','message' => __('messages.5025'),'result' => ''];
        }
    }

   /**
    * @DateOfCreation        06 Apr 2018
    * @ShortDescription      This function is responsible to convert string to date according to 
    *                        the format given .
    * @param                 String $timestamp ( String must be in unixtimestamp)
    *                        String $format ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date 
    *                        month and year according to need) You can remove hours minute and secods also
    * @return                Array with status, message and result(Date in the format provided 
    *                                                               in the parameter) 
    */
   public function timestampToDateTime($timestamp,$format)
    {
        if(!empty($timestamp) && !empty($format)){
            return ['code' => '1000','message' => __('messages.1017'),'result' => date($format,$timestamp)];
        }else{
            return ['code' => '5000','message' => __('messages.5020'),'result' => '' ];
        }
    }

   /**
    * @DateOfCreation        06 Apr 2018
    * @ShortDescription      This function is responsible to convert date to string.
    * @param                 Timestamp $date (Date to convert)
    *                        String $format ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    * @return                Array with status, message and result( timestamp )
    */
    public function dateTimeTotimestamp($date,$format)
    {
        $converted_date = $this->validateDate($date,$format);
        if(!empty($date) && !empty($converted_date['result'])){
            return ['code' => '1000','message' => __('messages.1018'),'result' => strtotime($converted_date['result'])];
        }else{
            return ['code' => '5000','message' => __('messages.5021'),'result' => ''];
        } 
    }

   /**
    * @DateOfCreation        06 Apr 2018
    * @ShortDescription      This function is responsible to convert date format.
    * @param                 Timestamp $date (Current date)
    *                        String $currentFormat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    *                        String  $newformat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    * @return                Array with status, message and result ( Date in the 
    *                        format provided in the parameter )
    */
    public function changeFormat($date,$currentFormat,$newformat)
    {
        $converted_date = $this->validateDate($date,$currentFormat);
        if(!empty($newformat) && !empty($converted_date['result'])){
            return ['code' => '1000','message' => __('messages.1019'),'result' => date($newformat,$this->dateTimeTotimestamp($converted_date['result']))];
        }else{
            return ['code' => '5000','message' => __('messages.5021'),'result' => ''];
        }
    }
    
   /**
    * @DateOfCreation        06 Apr 2018
    * @ShortDescription      This function is responsible to change the timezone of 
    *                        thedatetime and given new datetime in response with new 
    *                        timezone.
    * @param                 Timestamp $datetime ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    *                        String $currentFormat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    *                        String $oldtimezone ( PHP standard time zones)
    *                        String $newtimezone ( PHP standard time zones)  
    * @return                Array with status, message and result ( Datetime with new timezone )
    */
    public function convertTimeZone($datetime,$currentFormat,$oldtimezone,$newtimezone)
    {
        $converted_date = $this->validateDate($date,$currentFormat);
        if(!empty($converted_date['result']) && !empty($oldtimezone) && !empty($newtimezone)){
            $date = new \DateTime($datetime, new \DateTimeZone($oldtimezone));
            $date->setTimezone(new \DateTimeZone($newtimezone));
            return ['code' => '1000','message' => __('messages.1001'),'result' => $date->format('Y-m-d H:i:s')];
        }else{
            return ['code' => '5000','message' => __('messages.5023'),'result' => '' ];
        }
    }

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible to activity time  
    * @param                 TimeStamp $datetime ( date you need to check )
    *                        Boolean $full ( default false ) false for Approx time and 
    *                        true for exact time
    *                        String $currentFormat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    * @return                Array with status, message and result() 
    */
    function timeElapsed($datetime,$currentFormat, $full = false) {

        $converted_date = $this->validateDate($datetime,$currentFormat);
        if(!empty($converted_date['result'])){  
            $now = new \DateTime;
            $ago = new \DateTime($converted_date['result']);
            $diff = $now->diff($ago);

            $diff->w = floor($diff->d / 7);
            $diff->d -= $diff->w * 7;

            $string = array(
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            );
            foreach ($string as $k => &$v) {
                if ($diff->$k) {
                    $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                } else {
                    unset($string[$k]);
                }
            }

            if (!$full) $string = array_slice($string, 0, 1);
            $result = $string ? implode(', ', $string) . ' ago' : 'just now';
            return ['code' => '1000','message' => __('messages.1020'),'result' => $result];
       }else{
            return ['code' => '5000','message' => __('messages.5021'),'result' => ''];
       } 
    } 

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible to get different between two dates.
    * @param                 Timestamp $startDatetime
    *                        String $format    
    *                        Timestamp $endDatetime
    * @return                Array with status, message and result( timestamp )
    */
    public function dateDifference($startDatetime,$startDateFormat,$endDatetime,$endDateFormat)
     {
        $toDate = $this->validateDate($startDatetime,$startDateFormat);
        $fromDate = $this->validateDate($endDatetime,$endDateFormat);

        if(empty($toDate['result'])){
            return ['code' => '5000','message' => __('messages.5022'),'result' => ''];
        }
        if(empty($fromDate['result'])){
            return ['code' => '5000','message' => __('messages.5026'),'result' => ''];
        }
        $timeFirst  = $this->dateTimeTotimestamp($toDate['result'],$startDateFormat);
        $timeSecond = $this->dateTimeTotimestamp($fromDate['result'],$endDateFormat);
        $differenceInSeconds = $timeFirst['result'] - $timeSecond['result'];
        return ['code' => '1000','message' => __('messages.1021'),'result' => $differenceInSeconds];
     } 

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible to add days in the date  
    * @param                 TimeStamp $datetime ( date you need to check )
    *                        Boolean $full ( default false ) false for Approx time and 
    *                        true for exact time
    *                        String $currentFormat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    * @return                Array with status, message and result() 
    */
     public function addDaysTodate($date,$currentFormat)
     {
        $converted_date = $this->validateDate($date,$currentFormat);
        if(!empty($converted_date['result'])){
            $dateTimeTotimestamp = $this->dateTimeTotimestamp($converted_date['result'],$currentFormat);
            $newDate = date('Y-m-d', strtotime('+'.$days.' days', $dateTimeTotimestamp['result']));
            return ['code' => '1000', 'message' => __('message.1022'), 'result' => $newDate];
        }else{
            return ['code' => '5000', 'message' => __('message.5021'), 'result' => ''];
        }
     }
    
   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible to substract days from date  
    * @param                 TimeStamp $datetime ( date you need to check )
    *                        Boolean $full ( default false ) false for Approx time and 
    *                        true for exact time
    *                        String $currentFormat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    * @return                Array with status, message and result() 
    */
    public function substractDaysFromdate($date,$currentFormat)
    {
        $converted_date = $this->validateDate($date,$currentFormat);
        if(!empty($converted_date['result'])){
            $dateTimeTotimestamp = $this->dateTimeTotimestamp($converted_date['result'],$currentFormat);
            $new_ago = date('Y-m-d', strtotime('-'.$days.' days', $dateTimeTotimestamp['result']));
            return ['code' => '1000', 'message' => __('message.1023'), 'result' => $newDate];
        }else{
            return ['code' => '5000', 'message' => __('message.5021'), 'result' => ''];
        }  
    }

   /**
    * @DateOfCreation        16 Apr 2018
    * @ShortDescription      This function is responsible to get the Date or Month or year
    *                        in numeric  
    * @param                 TimeStamp $datetime ( date you need to check )
    *                        String $currentFormat ( options - dd/mm/yy H:i:s,dd/mm/YY H:i:s,
    *                        dd-mm-yy H:i:s,dd-mm-YY H:i:s You can change the position of date month and year according to need) You can remove hours minute and secods also
    *                        String $type  (d = date,m = month , Y = Year)
    * @return                Array with status, message and result() 
    */
    public function getYearMonthDate($datetime,$currentFormat,$type = 'd')
    {
        $converted_date = $this->validateDate($datetime,$currentFormat);
        if(!empty($converted_date['result'])){
            $timestamp = $this->dateTimeTotimestamp($datetime,$currentFormat);
            return ['code' => '1000','message' => __('message.1001'),'result' => date($type,$timestamp['result'])];
        }else{
            return ['code' => '5000','message' => __('message.5021'),'result' => ''];
        }
    }
}