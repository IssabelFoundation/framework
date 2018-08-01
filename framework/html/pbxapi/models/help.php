<?php

class help {

    function __construct($f3) {

        $this->db  = $f3->get('DB');

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');

        // If not authorized it will die out with 403 Forbidden
        $localauth = new authorize();
        $localauth->authorized($f3);
    }

    function display() {
        $cdir = scandir('controllers/');
        $results=array();
        foreach ($cdir as $key => $controller) {
            if (!in_array($controller,array(".",".."))) {
                $controller = preg_replace("/\.php/","",$controller);
                if(substr($controller,0,1)<>'.') {
                    $results[] = $controller;
                }
            }
        }
        $final = array();
        $final['controllers'] = $results;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();
    }
}
