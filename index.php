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

// Enable page compression
ob_start("ob_gzhandler");

// Get the route and stop number
$routeno = -1;
$stopno = -1;
$user_lat = -1;
$user_long = -1;

if (isset($_REQUEST["stopno"])) {
    $stopno = (int) $_REQUEST["stopno"];
}

if (isset($_REQUEST["routeno"])) {
    $routeno = (int) $_REQUEST["routeno"];
}

if (isset($_REQUEST["lat"])) {
    $user_lat = (float) $_REQUEST["lat"];
}

if (isset($_REQUEST["long"])) {
    $user_long = (float) $_REQUEST["long"];
}

if (isset($_REQUEST["output"])) {
    $outputmode = $_REQUEST["output"];
}

//print "<!-- stop: $stopno, route:$routeno, lat:$user_lat, long:$user_long -->";

$GPS_URL_TEMPLATE = "https://maps.google.com/maps?saddr=45.42153,-75.697193&daddr=45.421982,-75.697273&markers=%%%&dirflg=w";

$GMAPS = "<div class=\"map\">"
        . "<a href=\"https://maps.google.com/maps?"
        . "daddr=%%%_GPS_LAT_%%%,%%%_GPS_LON_%%%"
        . "&dirflg=w\">"
        . "<img src=\"http://maps.googleapis.com/maps/api/staticmap?"
        . "center=%%%_GPS_LAT_%%%,%%%_GPS_LON_%%%"
        . "&markers=%%%_GPS_LAT_%%%,%%%_GPS_LON_%%%"
        . "&zoom=12&size=250x250&sensor=false\" "
        . "alt=\"map\" />"
        . "</a></div>";

$CARD = " <section class=\"card\"><h1><strong><span class=\"arrival\">"
        . "%%%_MINUTES_%%%</span> minutes</strong> until the %%%_BUS_TYPE_%%% from "
        . "%%%_SRC_%%% to %%%_DST_%%% arrives.</h1> "
        . "%%%_MAP_%%%</section>";

$PAGE_LOAD_TIME_CARD = " <section class=\"card\">
            %%%_PAGE_LOAD_TIME_%%%
        </section>";

$AD_CARD = "<section class=\"card\">
            <h3>Advertisement</h3>
            <h3><div class=\"map\">
                <script async src=\"//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js\"></script>
                <!-- travvik-card -->
                <ins class=\"adsbygoogle\"
                     style=\"display:inline-block;width:250px;height:250px\"
                     data-ad-client=\"ca-pub-7746688859597966\"
                     data-ad-slot=\"4401611004\"></ins>
                <script>
                    (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
                </div>
            </h3>
            <h4><span class=\"arrival\">2</span> minute until advertisement is removed.</h4>
        </section>";

$ROUTE_CARD = " <section class=\"card\">
            <h1>
                <a href=\"?routeno=%%%_ROUTE_%%%&stopno=%%%_STOP_CODE_%%%\">
                <strong>%%%_ROUTE_%%%</strong>  at %%%_SRC_%%%.
                </a>
                </h1>
            <h2><a href=\"https://maps.google.com/maps?saddr=%%%_USER_LAT_%%%,%%%_USER_LON_%%%&daddr=%%%_GPS_LAT_%%%,%%%_GPS_LON_%%%&markers=%%%\">Google Maps directions to bus stop.</a></h2>
        </section>";

$cards = "";
$pullFromCacheStr = "None";

$page = file_get_contents("webview/index.template.html");

if ($routeno != -1 && $stopno != -1) {

    //print "<!-- Getting individual bus times for stop... -->";
// Our the OC Transpo utility class.
    $curBus = new OCTAPI;
// Check database if already in cache
    $database = new DATABASE($DB_URL, $DB_USER, $DB_PASS, $DB_NAME);
//$database->m_debug = false;
    $result = $database->getTripInCache($routeno, $stopno);
    if (mysqli_num_rows($result) == 0) {
        //echo "<br/> No rows found.";
        $pullFromCacheStr = "Pulled from OC TRANSPO.";
        // If not in cache pull and update from OC Transpo.    
        $rawsoap = $curBus->queryOcTranspo($OC_TRANSPO_TRIP_URL, $OC_TRANSPO_APP_KEY, $OC_TRANSPO_APP_ID, $stopno, $routeno);
        //$js = $curBus->endodeJson($curBus->dataset);
//    echo $rawsoap;
        $database->putTripInCache($routeno, $stopno, addslashes($rawsoap));
    } else {
        //echo "<br/> Row rows found.";
        $pullFromCacheStr = "Pulled from CACHE.";
        $row = mysqli_fetch_array($result);
        $curBus->pullSoapData($row['data']);
    }

    //For debugging purposes, I'm throwing the JSON object out 
    $json = $curBus->generate_jsonp($curBus->dataset);
    //print "<!-- Json: $json -->";

    for ($i = 0; $i < 3; $i++) {
        for ($y = 0; $y < 3; $y++) {
            $arrival = @$curBus->getTrip($y, $i)->AdjustedScheduleTime;
            $gpsLat = @$curBus->getTrip($y, $i)->Latitude;
            $gpsLong = @$curBus->getTrip($y, $i)->Longitude;
            //print "<!-- arrival: $arrival -->\n";
            //print "<!-- gpsLat: $gpsLat -->\n";
            //print "<!-- gpsLong: $gpsLong -->\n";


            if ($arrival == null) {
                $arrival = -1;
                continue;
            }

            /* Bus type String */
            $busType = "bus";    // returns "f"
            switch (substr($curBus->getTrip($y, $i)->BusType, 0, 1)) {

                case "6":
                    $busType = "bendy bus";
                    break;
                case "4":
                    $busType = "single decker";
                    break;
                case "D":
                    $busType = "double decker";
                    break;
            }
            
            $gmaps = "";
            if (($gpsLat != "" && $gpsLong != "") &&
                    ($gpsLat != 0 && $gpsLong != 0) &&
                    ($gpsLat != null && $gpsLong != null)
            ) {
                $gmaps = $GMAPS;
            }

            $card = $CARD;
            // Next arrival in...
            $card = str_replace("%%%_MINUTES_%%%", $arrival, $card);
            $card = str_replace("%%%_BUS_TYPE_%%%", $busType, $card);
            $card = str_replace("%%%_ROUTE_%%%", $routeno, $card);
            $card = str_replace("%%%_SRC_%%%", $curBus->nextTrips->StopLabel, $card);
            $card = str_replace("%%%_DST_%%%", $curBus->getTrip($y, $i)->TripDestination, $card);
            $card = str_replace("%%%_GPS_%%%", @$gpsLatLong, $card);
            $card = str_replace("%%%_DIRECTION_%%%", $curBus->getRoute($y)->Direction, $card);
            
            // MAP must be done before GPS'. 
            $card = str_replace("%%%_MAP_%%%", $gmaps, $card);
            $card = str_replace("%%%_GPS_LAT_%%%", $gpsLat, $card);
            $card = str_replace("%%%_GPS_LON_%%%", $gpsLong, $card);


            $cards = $cards . $card;
        }
    }
} else if ($user_lat != -1 && $user_long != -1 && $routeno == -1 && $stopno == -1) {

    //print "<!-- Getting route details for stop... -->";
    $database = new DATABASE($DB_URL, $DB_USER, $DB_PASS, $DB_NAME);
    
    $searchRadius = 0.000;
    $result = array();
    while(count(@mysqli_fetch_array($result)) == 0 && $searchRadius < 0.05){
        $searchRadius = $searchRadius + 0.001;
        $result = $database->getStopFromLatLong((float) $user_lat, (float) $user_long, $searchRadius);
        //print "count " . count(mysqli_fetch_array($result)) . "<br/>";
    }
    
    // I have a suspecion that mysqli_fetch_array destroys $result as a side effect.
    $result = $database->getStopFromLatLong((float) $user_lat, (float) $user_long, $searchRadius);
        
    //$database->setDebugMode(true);

    $list = array(-1);

    while ($row = mysqli_fetch_array($result)) {

        $stopno = (int) $row['stop_code'];
        $stopname = (string)$row['stop_name'];
        $stoplon = (float) $row['stop_lon'];
        $stoplat = (float) $row['stop_lat'];

        //print "<!-- Checking stop: $stopno -->";

        if (array_search($stopno, $list) == true) {
            // Don't do duplicates.
            continue;
        }
        array_push($list, $stopno);

        //////print "<!-- Print details stop: $stopno -->";


        $curBus2 = new OCTAPI;

        $result = $database->getStopInCache($stopno);
        if (mysqli_num_rows($result) == 0) {
            $pullFromCacheStr = "Pulled from oc transpo." . "</br>Search radius: " . ($searchRadius / 0.00001)  ." metres.</br>";
            // If not in cache pull and update from OC Transpo.    
            $rawsoap = $curBus2->queryOcTranspo($OC_TRANSPO_STOP_DETAILS_URL, $OC_TRANSPO_APP_KEY, $OC_TRANSPO_APP_ID, $stopno, 0);
            $database->putStopInCache($stopno, $rawsoap);
        } else {
            $pullFromCacheStr = "Pulled from cache.</br>Search radius: " . ($searchRadius / 0.00001)  ." metres.</br>";
            $row = mysqli_fetch_array($result);
            $curBus2->pullSoapData($row['data']);
        }
        $json = $curBus2->generate_jsonp($curBus2->dataset);
        ////print "<!-- JSON: $json  -->\n";

        $numOfRoutes = count($curBus2->dataset->GetRouteSummaryForStopResponse->GetRouteSummaryForStopResult->Routes->Route);
        $cnt = 0;
        $routeno = 0;
        
        //print  "<!-- numOfRoutes: $numOfRoutes\n  -->";

        $alreadyDoneRoute = array(-1);
        for ($cnt = 0; $cnt < $numOfRoutes; $cnt++) {

            $routeno = $curBus2->dataset->GetRouteSummaryForStopResponse->GetRouteSummaryForStopResult->Routes->Route[$cnt];
            $routeno = (int) $routeno->RouteNo;
            //print  "<!-- routeno: $routeno\n  -->";

            if (in_array($routeno, $alreadyDoneRoute)) {
                // Don't do duplicates.
                continue;
            }
            array_push($alreadyDoneRoute, $routeno);
            
            //print  "<!-- array_push alreadyDoneRoute: $alreadyDoneRoute\n  -->";
                 
            $card = $ROUTE_CARD;
            $card = str_replace("%%%_ROUTE_%%%", $routeno, $card);
            $card = str_replace("%%%_SRC_%%%", $stopname, $card);
            $card = str_replace("%%%_STOP_CODE_%%%", $stopno, $card);
            $card = str_replace("%%%_USER_LAT_%%%", $user_lat, $card);
            $card = str_replace("%%%_USER_LON_%%%", $user_long, $card);
            $card = str_replace("%%%_GPS_LAT_%%%", $stoplat, $card);
            $card = str_replace("%%%_GPS_LON_%%%", $stoplon, $card);


//            if ((($cnt + 1) % 3) == 0) {
//                $cards = $cards . $AD_CARD;
//            }
            $cards = $cards . $card;
            
            //print  "<!-- card: $card\n  -->";
        }
    }
}


//$pageLoadTime = microtime() - $start_time;
$pageLoadTime = microtime(true) - (float) $_SERVER["REQUEST_TIME_FLOAT"];

$card = str_replace("%%%_PAGE_LOAD_TIME_%%%", 'Load time: ' . $pageLoadTime . ' seconds. ' . $pullFromCacheStr, $PAGE_LOAD_TIME_CARD);
$cards = $cards . $card;

if ($routeno == -1 || $stopno == -1) {
    $page = str_replace("%%%_ROUTE_NO_%%%", "", $page);
    $page = str_replace("%%%_STOP_NO_%%%", "", $page);
} else if ($user_lat != -1 && $user_long != -1) {
    $page = str_replace("%%%_ROUTE_NO_%%%", "", $page);
    $page = str_replace("%%%_STOP_NO_%%%", "", $page);
} else {
    $page = str_replace("%%%_ROUTE_NO_%%%", $routeno, $page);
    $page = str_replace("%%%_STOP_NO_%%%", $stopno, $page);
}
$page = str_replace("%%%_CARDS_%%%", $cards, $page);

print $page;
?>
