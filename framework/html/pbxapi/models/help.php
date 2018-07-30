<?php

class help {
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
