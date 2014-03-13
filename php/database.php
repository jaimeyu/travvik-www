<?php

/*
* The MIT License (MIT)
* 
* Copyright (c) 2014 Jaime Yu
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
* 
*/

include "php/credentials.php";

/**
 * Description of database
 *
 * @author jaime
 */
class DATABASE {

    var $m_database;
    var $m_debug;

    function __construct($url, $user, $pass, $name) {
        $this->start($url, $user, $pass, $name);
        $this->m_debug = false;
    }

    function setDebugMode($mode) {
        $this->m_debug = $mode;
    }

    function start($url, $user, $pass, $name) {
    // Create connection
        $this->m_database = mysqli_connect($url, $user, $pass, $name);

    // Check connection
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
    }

    function query($cmd) {

        // debug
        if ($this->m_debug == true) {
            echo "<br/><br/>DEBUG::SQL::\n $cmd ::DEBUG::SQL::\n<br/><br/>";
        }

        $result = mysqli_query($this->m_database, $cmd);
        if (mysql_errno() != 0) {
            echo "Failed to query db: " . mysql_errno();
            $result = null;
        }

        return $result;
    }

    /**
     * 
     * @param type $lat
     * @param type $long
     * @return type
     * 
     * 
      Accuracy versus decimal places
      decimal
      places	degrees	N/S or
      E/W at equator	E/W at
      23N/S	E/W at
      45N/S	E/W at
      67N/S
      0	1.0	111.32 km	102.47 km	78.71 km	43.496 km
      1	0.1	11.132 km	10.247 km	7.871 km	4.3496 km
      2	0.01	1.1132 km	1.0247 km	.7871 km	.43496 km
      3	0.001	111.32 m	102.47 m	78.71 m	43.496 m
      4	0.0001	11.132 m	10.247 m	7.871 m	4.3496 m
      5	0.00001	1.1132 m	1.0247 m	.7871 m	.43496 m
      6	0.000001	111.32 mm	102.47 mm	78.71 mm	43.496 mm
      7	0.0000001	11.132 mm	10.247 mm	7.871 mm	4.3496 mm
      8	0.00000001	1.1132 mm	1.0247 mm	.7871 mm	.43496 mm
     */
    function getStopFromLatLong($lat, $long, $radius) {
        //$radius = 0.001; // roughyl 500m from your position. 
        $lat_low = $lat - $radius;
        $lat_high = $lat + $radius;
        $long_low = $long - $radius;
        $long_high = $long + $radius;
        $sql = "SELECT * FROM `stops` WHERE `stop_lat` >=$lat_low AND `stop_lat` <= $lat_high "
                . " AND `stop_lon` >= $long_low AND `stop_lon` <= $long_high LIMIT 0, 100 ";
        $result = $this->query($sql);

        return $result;
    }

    function getTripInCache($route, $stop) {
        $route = sprintf("%04d", (int) $route);
        $stop = sprintf("%04d", (int) $stop);
        $hash = $route . $stop;

        $sql = 'SELECT * FROM `time_cache` WHERE routestop=' . $hash . ' AND'
                . ' `timestamp` > date_sub(now(), interval 1 minute)';

        $result = $this->query($sql);
        return $result;
    }

    function putTripInCache($route, $stop, $data) {
        $route = sprintf("%04d", (int) $route);
        $stop = sprintf("%04d", (int) $stop);
        $hash = $route . $stop;
        $hash = trim($hash);

        $sql = "INSERT INTO time_cache (routestop, data) VALUES ('$hash','$data') "
                . "on duplicate key update data='$data'";

        $result = $this->query($sql);
        return $result;
    }
    
    function getStopInCache( $stop) {
        $stop = sprintf("%04d", (int) $stop);

        $sql = 'SELECT * FROM `stop_cache` WHERE stop_code=' . $stop . ' AND'
                . ' `timestamp` > date_sub(now(), interval 1 hour)';

        $result = $this->query($sql);
        return $result;
    }

    function putStopInCache($stop, $data) {
        $stop = sprintf("%04d", (int) $stop);

        $sql = "INSERT INTO stop_cache (stop_code, data) VALUES ('$stop','$data') "
                . "on duplicate key update data='$data'";

        $result = $this->query($sql);
        return $result;
    }

}
