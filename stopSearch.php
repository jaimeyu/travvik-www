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
include "php/database.php";

header('Content-type: application/javascript; charset=utf-8;gzip;');

ob_start("ob_gzhandler");

class STOP_SEARCH {

    var $m_database;
    var $m_debug;

    function __construct(&$database) {
        $this->m_database = $database;
        $this->m_debug = false;
    }

   
    /**
     * This method will search the database for a 
     * stop number using the information given
     * 
      Algorithm
     * 
     * The database contains a list of stops and their street names.
     * Example:
     * 
     * stop_id	 stop_code	 stop_name	 stop_desc	 stop_lat	 stop_lon
     * AD320	7092	QUEEN MARY / NORTH RIVER	 	45.424282	-75.665634
     * 
     * 
     * @param string $str User input, escape will be done here.
     * @return string JSON result of the search
     */
    function findStopNumberJson($str) {

        //print "str: $str\n";

        // Replace 'and' with '/'. This is how the intersections are defined in the db.
        $str = str_ireplace("and", "/", $str);


        $strArray = explode(" ", $str);

        if ($this->m_debug) print_r($strArray);
        $construedStr = "";
        $cnt = count($strArray);

        if ($this->m_debug) print "Count: " . count($strArray);
        for ($i = 0; $i < $cnt; $i++){
            $word  = $strArray[$i];
            $construedStr = $construedStr . "\"$word" . "%\" ";
        }

        $query = "SELECT * 
                  FROM  `stops` 
                  WHERE  `stop_name` LIKE  ($construedStr)
                  LIMIT 0 , 30";

        if ($this->m_debug) print "<!-- Getting route details for stop... -->\n";
        if ($this->m_debug) print "<!-- query: $query -->\n";
        if ($this->m_debug) $this->m_database->setDebugMode = true;
        $result = $this->m_database->query($query);
        
        $table = array();
        while ($row = mysqli_fetch_assoc($result)) {
            if ($this->m_debug) print_r($row);
            if ($this->m_debug) print "STOPID: " . $row['stop_id'] . ".end.\n";
            array_push($table, $row);
        }
        return $table;
    }

}

$dbSearch = new STOP_SEARCH(new DATABASE($DB_URL, $DB_USER, $DB_PASS, $DB_NAME));

$searchStr = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

$data = $dbSearch->findStopNumberJson($searchStr);

//print"Your input: $searchStr<br/>\n";
//print "Data: $data<br/>\n";
print json_encode($data);
