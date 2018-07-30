<?php

class asmanager {
    protected $conn;
    protected $ami;

    function __construct($f3) {

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');
        //header("Access-Control-Allow-Origin: *");

        // If not authorized it will die out with 403 Forbidden
        $localauth = new authorize();
        $localauth->authorized($f3);

        $mgrpass    = $f3->get('MGRPASS');
        $this->ami  = new asteriskmanager();
        $this->conn = $this->ami->connect("localhost","admin",$mgrpass);
        if(!$this->conn) {
           header('Content-Type: application/json');
           echo "{\"status\":\"error\",\"reason\":\"Could not connect to AMI\"}";
           die();
        }
    }

}

