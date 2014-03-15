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

class OCTAPI {

    var $dataset;
    var $nextTrips;
    var $busRoute;

    function curl($url, $fields, $fields_string) {
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, $fields);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    function encodeFieldsToUrl($fields) {

        $fields_string = "";

        //url-ify the data for the POST
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');
        return $fields_string;
    }

    function queryOcTranspo($url, $appkey, $appid, $stopno, $routeno) {
        $fields = array(
            'appID' => urlencode($appid),
            'apiKey' => urlencode($appkey),
            'stopNo' => urlencode($stopno),
            'routeNo' => urlencode($routeno)
        );
        $fields_string = $this->encodeFieldsToUrl($fields);

        $result = $this->curl($url, count($fields), $fields_string);
        $this->pullSoapData($result);
        return $result;
    }

    function pullSoapData($soap) {

        $this->dataset = simplexml_load_string($soap);
        //$this->registerXPathNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope/");
        $this->dataset->registerXPathNamespace('soap', 'envelope.xml');
        $this->dataset = $this->dataset->xpath('/soap:Envelope/soap:Body');
        $this->dataset = $this->dataset[0];

        if ($this->dataset->GetNextTripsForStopResponse->getName() != "") {
            $this->nextTrips = $this->dataset->GetNextTripsForStopResponse->GetNextTripsForStopResult;

            $this->busRoute = $this->nextTrips->Route->RouteDirection;


            return $this->dataset;
        }
        else if ($this->dataset->GetRouteSummaryForStopResponse->getName() != "") {
            $this->nextTrips = $this->dataset->GetRouteSummaryForStopResponse->GetRouteSummaryForStopResult;

            $this->busRoute = $this->nextTrips->Routes->Route;

            //print_r($this->dataset);
            //exit();

            return $this->dataset;        
        }


    }

    function getNbOfActiveRoutes() {
        return count($this->busRoute);
    }

    function getRoute($routeIndex) {
        if ($routeIndex < $this->getNbOfActiveRoutes() && $routeIndex >= 0)
            return $this->busRoute[$routeIndex];

        return null;
    }

    function getNbOfTrips($routeIndex) {
        if ($routeIndex < $this->getNbOfActiveRoutes() && $routeIndex >= 0)
            return count($this->busRoute[$routeIndex]->Trips->Trip);

        return null;
    }

    function getTrip($routeIndex, $tripIndex) {


        // Not really sure what this was trying to do..
        /*
        if (($routeIndex >= 0) &&
                ($routeIndex < $this->getNbOfTrips($routeIndex)) &&
                ($tripIndex >= 0) &&
                ($tripIndex < $this->getNbOfTrips($routeIndex))
        ) {*/
        if (true) {

            $item = $this->busRoute[$routeIndex]->Trips->Trip[$tripIndex];
            if ($item == null) {
                return 0;
            }
            return $item;
        }

        // If no trip data, return 0;
        return 0;
    }

    function getTripsInDirection($routeIndex, $direction) {
        $nbItems = $this->getNbOfTrips($routeIndex);
        $count;
        foreach ($this->busRoute as $item) {
            //if ($item->Trips)
        }
    }

    // Print to JSON
    function generate_jsonp($data) {
        if (preg_match('/\W/', @$_GET['callback'])) {
            // if $_GET['callback'] contains a non-word character,
            // this could be an XSS attack.
            header('HTTP/1.1 400 Bad Request');
            exit();
        }
        return sprintf('%s', json_encode($data));
    }

    // Print to JSON
    function endodeJson($data) {
        return json_encode($data);
    }

    // Take JSON and create the structure
    function decodeJson($json) {
        if (preg_match('/\W/', $_GET['callback'])) {
            // if $_GET['callback'] contains a non-word character,
            // this could be an XSS attack.
            header('HTTP/1.1 400 Bad Request');
            exit();
        }
        //return sprintf('%s', json_encode($data));
        $this->dataset = json_decode($json);
    }

}

?>
