<?php
use Firebase\JWT\JWT;

class jwtauth {

    protected $data;

    protected $db;

    protected $pwd;

    function __construct($f3) {

        $this->db  = new DB\SQL( 'sqlite:/var/www/db/acl.db' );

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');
        //header("Access-Control-Allow-Origin: *");

        try {
            $this->data = new DB\SQL\Mapper($this->db,'acl-user');
        } catch(Exception $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        if(is_file("/etc/issabel.conf")) {
            $data      = parse_ini_file("/etc/issabel.conf");
            $this->pwd = $data['amiadminpwd'];
        }

    }

    function get($f3) {
        //
        // Retrieve access and refresh tokens from PHP session, or refresh tokens if authenticate/refresh is called
        //
        session_name("issabelSession");
        session_start();

        if($f3->get('PARAMS.id')=='') {
            // returns tokens from php session
            header('Content-Type: application/json');
            if($_SESSION['access_token']<>'') {
                $jwt        = $_SESSION['access_token'];
                $jwtrefresh = $_SESSION['refresh_token'];
                echo "{\"access_token\":\"$jwt\",\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\",\"status\":\"authorized\"}";
            } else {
                echo "{\"status\":\"unauthorized\"}";
            }
            die();

        } else {
            //
            // try to refresh tokens
            //
            $key = $f3->get('JWT_KEY');

            try {

                $rnt  = $f3->get('GET.refresh_token');
                $jwt  = $f3->get('GET.access_token');
                $data = JWT::decode($rnt, $key, array('HS256'));

            }catch(Exception $e) {

                if($e->getMessage()=="Expired token") {

                    echo "{\"status\": \"expired\", \"type\": \"refresh\"}";
                    die();
                }
            }

            // refresh token is ok, extract payload from expired token to generate a new one with new expiration
            //
            try {
                JWT::$leeway = 720000;
                $data = JWT::decode($jwt, $key, array('HS256'));

                $time = time();
                $exp  = $f3->get('JWT_EXPIRES');

                $token = array(
                    'iat'  => $time,
                    'exp'  => $time + $exp,
                    'data' => $data->data
                );

                $tokenrefresh = array(
                    'iat' => $time,
                    'exp' => $time + ( $exp * 24 ),
                    'data' => [ ]
                );

                $jwt        = JWT::encode($token, $key);
                $jwtrefresh = JWT::encode($tokenrefresh, $key);

                echo "{\"access_token\":\"$jwt\",\"expires_in\":$exp,\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\",\"status\":\"authorized\"}";
                die();

            } catch(Exception $e) {
                echo "{\"status\": \"unauthorized\"}";
            }

        }
    }

    function put($f3) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
        die();
    }

    function post($f3) {
        //
        // authenticate user and return a set of access and refresh tokens
        //
        $user = $f3->get('POST.user');
        if(!isset($user)) {
            $user = $f3->get('POST.username');
        }
        $password    = $f3->get('POST.password');
        $md5password = md5($password);

        //  $result = $this->db->exec('SELECT * FROM acl_user WHERE name = :name AND md5_password = :md5password',array(':name'=>$user,':md5password'=>$md5password));
        //  if($this->db->count() > 0) {

        if($user=='admin' && $password==$this->pwd) {

            $time = time();
            $key  = $f3->get('JWT_KEY');
            $exp  = $f3->get('JWT_EXPIRES');

            $token = array(
                'iat' => $time,
                'exp' => $time + $exp,
                'data' => [
                    'name' => $user
                ]
            );

            $tokenrefresh = array(
                'iat' => $time,
                'exp' => $time + ( $exp * 24 ),
                'data' => [ ]
            );

            $jwt = JWT::encode($token, $key);
            $jwtrefresh = JWT::encode($tokenrefresh, $key);

            header('Content-Type: application/json');
            echo "{\"access_token\":\"$jwt\",\"expires_in\":$exp,\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\"}";

            die();

        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
            die();
        }

    }
}
