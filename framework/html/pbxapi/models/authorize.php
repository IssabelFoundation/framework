<?php
use Firebase\JWT\JWT;

class authorize {

    protected $whitelist = array( '127.0.0.1', '::1');

    function authorized($f3) {

        $headers = $f3->get('HEADERS');

        if(!$f3->exists('HEADERS.Authorization')) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Unauthorized', true, 403);
            die();
        }

        list (,$jwt) = preg_split("/ /",$headers['Authorization']);
        $key = $f3->get('JWT_KEY');

        try {
            $data = JWT::decode($jwt, $key, array('HS256'));
        }catch(Exception $e) {

            if($e->getMessage()=="Expired token") {
                JWT::$leeway = 720000;
                $decoded = JWT::decode($jwt, $key, array('HS256'));
                echo "{\"status\": \"expired\"}";
            } else {
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Unauthorized', true, 403);
            }
            die();
        }

        // Token ok, then just return
        return;

        /*
        if($f3->get('DOAUTH')==false) {
            return;
        }

        if(in_array($_SERVER['REMOTE_ADDR'], $this->whitelist)){
            // always accept from localhost
            return;
        }
        */
    }
}
