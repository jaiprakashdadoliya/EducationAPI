<?php
namespace App\Libraries;
use Illuminate\Http\Request;
/**
 * MapLib Class
 *
 * @package                Laravel Base Setup
 * @subpackage             MapLib
 * @category               Library
 * @DateOfCreation         31 July 2018
 * @ShortDescription       This Library is responsible for Map functions that are small but usefull
 */

class MapLib {

   /**
    * @DateOfCreation        31 July 2018
    * @ShortDescription      This function is responsible to get route polylen
    * @param                 String $origin
    *                        String $destination
    *                        String $waypoints
    * @return                integer
    */

    public function getPolylenForDirection($origin, $destination, $waypoints) {
        $markers = array();
        $waypoints_labels = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K");
        $waypoints_label_iter = 0;
        $markers[] = "markers=color:green" . urlencode("|") . "label:" . urlencode($waypoints_labels[$waypoints_label_iter++] . '|' . $origin);
        foreach ($waypoints as $waypoint) {
            $markers[] = "markers=color:blue" . urlencode("|") . "label:" . urlencode($waypoints_labels[$waypoints_label_iter++] . '|' . $waypoint);
        }
        $markers[] = "markers=color:red" . urlencode("|") . "label:" . urlencode($waypoints_labels[$waypoints_label_iter] . '|' . $destination);
        $departureTime = time();
        $url = "https://maps.googleapis.com/maps/api/directions/json?origin=$origin&destination=$destination&sensor=false&departureTime=".$departureTime."&alternatives=false&trafficModel=best_guess&waypoints=" . implode($waypoints, '|'). "&travelMode=DRIVING&key=AIzaSyAp5mYt-ABG-z7sxfzDakdZ9-SA7qKYwo0";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        $result = curl_exec($ch);
        curl_close($ch);
        $googleDirection = json_decode($result, true);
        //For Draw Map
        /*$polyline = urlencode($googleDirection['routes'][0]['overview_polyline']['points']);
        $markers = implode($markers, '&');
        
        return "https://maps.googleapis.com/maps/api/staticmap?size=500x500&maptype=roadmap&path=enc:$polyline&$markers";*/

        $polylineData = array();
        $legs = $googleDirection['routes'][0]['legs'];
        foreach ($legs as $steps) {
            foreach ($steps['steps'] as $value) {
                $polylineData[] = $value['polyline']['points'];
            }
        }
        return $polylineData;
    }

    /**
    * @DateOfCreation        09 Aug 2018
    * @ShortDescription      This function is responsible to get Lat and Long by polylen strings
    * @param                 String $polylinr
    * @return                lat,long
    */

    public function getLatLongbyPolylen($string) {
        # Do steps 1-11 given here 
            # https://developers.google.com/maps/documentation/utilities/polylinealgorithm
            # in reverse order and inverted (i.e. left shift -> right shift, add -> subtract)

            //$string = "udgiEctkwIldeRe}|x@cfmXq|flA`nrvApihC";
            # Step 11) unpack the string as unsigned char 'C'
            $byte_array = array_merge(unpack('C*', $string));
            $results = array();

            $index = 0; # tracks which char in $byte_array
            do {
              $shift = 0;
              $result = 0;
              do {
                $char = $byte_array[$index] - 63; # Step 10
                # Steps 9-5
                # get the least significat 5 bits from the byte
                # and bitwise-or it into the result
                $result |= ($char & 0x1F) << (5 * $shift);
                $shift++; $index++;
              } while ($char >= 0x20); # Step 8 most significant bit in each six bit chunk
                # is set to 1 if there is a chunk after it and zero if it's the last one
                # so if char is less than 0x20 (0b100000), then it is the last chunk in that num

              # Step 3-5) sign will be stored in least significant bit, if it's one, then 
              # the original value was negated per step 5, so negate again
              if ($result & 1)
                $result = ~$result;
              # Step 4-1) shift off the sign bit by right-shifting and multiply by 1E-5
              $result = ($result >> 1) * 0.00001;
              $results[] = $result;
            } while ($index < count($byte_array));

            # to save space, lat/lons are deltas from the one that preceded them, so we need to 
            # adjust all the lat/lon pairs after the first pair
            for ($i = 2; $i < count($results); $i++) {
              $results[$i] += $results[$i - 2];
            }

            # chunk the array into pairs of lat/lon values
            //dd(array_chunk($results, 2));
            return array_chunk($results, 2);

            # Test correctness by using Google's polylineutility here:
            # https://developers.google.com/maps/documentation/utilities/polylineutility
    }
}