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

$database = new DATABASE($DB_URL, $DB_USER, $DB_PASS, $DB_NAME);
$database->setDebugMode(true);
$result = $database->getStopFromLatLong((int) 45.130103, (int) -75.716354);

echo "<br/>";
echo "<br/>";
echo "LATLONG DB GRAB<br/>";

while ($row = mysqli_fetch_array($result)) {
    echo $row['stop_name'] . " " . $row['stop_code'];
    echo "<br>";
}


echo "<br/>";
echo "<br/>";
echo "TRIP CACHE<br/>";

$result = $database->getTripInCache(97, 3038);
while ($row = mysqli_fetch_array($result)) {
    echo $row['routestop'] . " " . $row['json'] . " " . $row['timestamp'];
    echo "<br>";
}

echo "<br/>";
echo "Splicing debug data into db";
$database->putTripInCache(5, 5, "This is some test data. Normally would be JSON." . microtime());
//$database->putTripInCache(98, 3038, "This is some test data. Normally would be JSON." . microtime());
//$database->putTripInCache(99, 3038, "This is some test data. Normally would be JSON." . microtime());

echo "<br/>";
echo "TRIP CACHE<br/>";

$result = $database->getTripInCache(5, 5);
while ($row = mysqli_fetch_array($result)) {
    echo $row['routestop'] . " " . $row['json'] . " " . $row['timestamp'];
    echo "<br>";
}

echo "<br/>";
echo "TRIP CACHE<br/>";
$result = $database->getTripInCache(99, 3038);
while ($row = mysqli_fetch_array($result)) {
    echo $row['routestop'] . " " . $row['json'] . " " . $row['timestamp'];
    echo "<br>";
}

$result = $database->getTripInCache(99, 3038);
$num_rows = mysqli_num_rows($result);
echo "ROWSSS:" . $num_rows . " .....";
$row = mysqli_fetch_array($result);
echo "<br/> RoWS: " . $row['json'] ." ssss<br/>";

echo "<br/>";
echo "Empty TRIP CACHE<br/>";
$result = $database->getTripInCache(1, 1);
if (mysql_num_rows($result) == 0){
    echo "<br/> No rows found.";
}

echo "<br/>";
echo "<br/>";
$pageLoadTime = microtime(true) - (int) $_SERVER["REQUEST_TIME_FLOAT"];
echo "Page Load time: " . $pageLoadTime;
