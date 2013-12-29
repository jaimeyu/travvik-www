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

include "php/octapi.php";
include "php/database.php";

header('Content-type: application/javascript; charset=utf-8;gzip;');

ob_start("ob_gzhandler");
// Our the OC Transpo utility class.
$curBus = new OCTAPI;


// Get the route and stop number
$routeno = 118;
$stopno = 3034;
if (isset($_REQUEST["stopno"])) {
    $stopno = $_REQUEST["stopno"];
    //echo "stop no: $routeno<br/>";
}
if (isset($_REQUEST["routeno"])) {
    $routeno = $_REQUEST["routeno"];
    //echo "route no: $routeno<br/>";
}

$debug = 0;
if (isset($_GET ["debug"])) {
    $debug = intval($_GET["debug"]);

    if ($debug == 1) {
        $data = file_get_contents("sample-data/data.json", "r");
        print $data;
        return 0;
    }
}

// Check database if already in cache
$database = new DATABASE($DB_URL, $DB_USER, $DB_PASS, $DB_NAME);
//$database->m_debug = false;
$result = $database->getTripInCache($routeno, $stopno);
if (mysqli_num_rows($result) == 0) {
    //echo "<br/> No rows found.";
    // If not in cache pull and update from OC Transpo.    
    $rawsoap = $curBus->queryOcTranspo($OC_TRANSPO_TRIP_URL, $OC_TRANSPO_APP_KEY, $OC_TRANSPO_APP_ID, $stopno, $routeno);
    
    $database->putTripInCache($routeno, $stopno, addslashes($rawsoap));
    print $curBus->generate_jsonp($curBus->dataset);
} else {
    //echo "<br/> Row rows found.";
    $row = mysqli_fetch_array($result);
    $data = $row['data'];
  
    $curBus->pullSoapData($data);
    
    print $curBus->generate_jsonp($curBus->dataset);
}
