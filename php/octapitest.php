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

include "./database.php";
include "octapi.php";

// Enable page compression
ob_start("ob_gzhandler");

if (isset($_REQUEST["lat"])) {
    $lat = (float) $_REQUEST["lat"];
}

if (isset($_REQUEST["long"])) {
    $long = (float) $_REQUEST["long"];
}

$database = new DATABASE($DB_URL, $DB_USER, $DB_PASS, $DB_NAME);

$result = $database->getStopFromLatLong((float) $lat, (float) $long, 0.001);
//$database->setDebugMode(true);
while ($row = mysqli_fetch_array($result)) {

    $stopno = $row['stop_code'];
    $stopname = $row['stop_name'];
    //print "<br/>Working on getting data for stop: " . $stopno . "<br/>";

    $curBus2 = new OCTAPI;

    $result = $database->getStopInCache($stopno);
    if (mysqli_num_rows($result) == 0) {
        print "Pulled from oc transpo." . "</br>";
        // If not in cache pull and update from OC Transpo.    
        $rawsoap = $curBus2->queryOcTranspo($OC_TRANSPO_STOP_DETAILS_URL, $OC_TRANSPO_APP_KEY, $OC_TRANSPO_APP_ID, $stopno, 0);
        $database->putStopInCache($stopno, $rawsoap);
    } else {
        print "Pulled from cache." . "</br>";
        $row = mysqli_fetch_array($result);
        
        //print $row['data'];
        $curBus2->pullSoapData($row['data']);
    }

    //print $curBus2->generate_jsonp($curBus2->dataset) . "</br>";
    
    $numOfRoutes = count($curBus2->dataset->GetRouteSummaryForStopResponse->GetRouteSummaryForStopResult->Routes->Route);
    print "Found " . $numOfRoutes . " of routes for $stopname ($stopno).</br>";
    $cnt = 0;
    $routeno = 0;
    print "<ul>";
    for ($cnt = 0; $cnt < $numOfRoutes; $cnt++) {
        //print "<br/>COUNT :: " . $cnt . "</br>";
        
        $routeno = $curBus2->dataset->GetRouteSummaryForStopResponse->GetRouteSummaryForStopResult->Routes->Route[$cnt];
        $routeno = $routeno->RouteNo;
        
        print "<li><a href='index.php?routeno=$routeno&stopno=$stopno'>See upcoming times for Route " . $routeno . " At stop " . $stopname. " (" . $stopno . ").</a></li>";
    }
    print "</ul>";
}

$pageLoadTime = microtime(true) - (float) $_SERVER["REQUEST_TIME_FLOAT"];
print "<br/>Page load time: " . $pageLoadTime . "seconds <br/>";
?>
