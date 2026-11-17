<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2018 Issabel Foundation                                |
  +----------------------------------------------------------------------+
  | This program is free software: you can redistribute it and/or modify |
  | it under the terms of the GNU General Public License as published by |
  | the Free Software Foundation, either version 3 of the License, or    |
  | (at your option) any later version.                                  |
  |                                                                      |
  | This program is distributed in the hope that it will be useful,      |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
  | GNU General Public License for more details.                         |
  |                                                                      |
  | You should have received a copy of the GNU General Public License    |
  | along with this program.  If not, see <http://www.gnu.org/licenses/> |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is Issabel LLC            |
  +----------------------------------------------------------------------+
  $Id: jwtauth.php, Wed 18 May 2022 08:17:16 AM EDT, nicolas@issabel.com
*/

use Firebase\JWT\JWT;

require_once(dirname(__FILE__) . '/../lib/compat.php');

class jwtauth {

    //protected $data;

    //protected $db;

    protected $pwd;

    function __construct($f3) {

        // $this->db  = new DB\SQL( 'sqlite:/var/www/db/acl.db' );

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');

        /*
        try {
            $this->data = new DB\SQL\Mapper($this->db,'acl-user');
        } catch(Exception $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }
        */

        if(is_file("/etc/issabel.conf")) {
            $data = $this->parse_conf("/etc/issabel.conf");
            if(isset($data['amiadminpwd'])) {
                $this->pwd = $data['amiadminpwd'];
            }
        }

    }

    function parse_conf($file) {
        // Delegado al parser unico de lib/compat.php: el preg_split
        // anterior truncaba el valor en el primer "=" y hacia que el
        // password comparado no fuera el del archivo.
        return pbxapi_parse_conf($file);
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

    /**
     * Responde 403 y termina. Usa HTTP/1.1 si SERVER_PROTOCOL no esta
     * definido (por ejemplo, al ejecutar desde consola en pruebas).
     */
    protected static function denyForbidden() {
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol . ' 403 Forbidden', true, 403);
        die();
    }

    function put($f3) {
        self::denyForbidden();
    }

    /**
     * Compara credenciales de forma segura.
     *
     * Devuelve true solo si:
     *  - usuario y password son strings (no float/int/bool/array/null),
     *  - el usuario es exactamente 'admin',
     *  - el password esperado no esta vacio,
     *  - el password coincide en tiempo constante.
     *
     * El chequeo is_string() es lo que cierra el type juggling: un body
     * JSON como {"user":"admin","password":0e999999} decodifica a
     * float(0), y en PHP < 8 la comparacion 0 == "cualquier password"
     * devolvia true (el string se casteaba a numero).
     *
     * @param  mixed  $user         usuario recibido
     * @param  mixed  $password     password recibido
     * @param  mixed  $expected_pwd password esperado (amiadminpwd)
     * @return bool
     */
    public static function credentialsMatch($user, $password, $expected_pwd) {
        if(!is_string($user) || !is_string($password)) {
            return false;
        }
        if(!is_string($expected_pwd) || $expected_pwd === '') {
            return false;
        }
        if($user !== 'admin') {
            return false;
        }
        return hash_equals($expected_pwd, $password);
    }

    function post($f3) {
        //
        // authenticate user and return a set of access and refresh tokens
        //
        $input = json_decode($f3->get('BODY'),true);
        if(!is_array($input)) {
            $input = array();
        }

        $user = isset($input['user'])?$input['user']:$f3->get('POST.user');
        if(!isset($user)) {
            $user = isset($input['username'])?$input['username']:$f3->get('POST.username');
        }
        $password = isset($input['password'])?$input['password']: $f3->get('POST.password');

        if(!self::credentialsMatch($user, $password, $this->pwd)) {
            self::denyForbidden();
        }

        $time = time();
        $key  = $f3->get('JWT_KEY');
        $exp  = $f3->get('JWT_EXPIRES');

        $token = array(
            'iat' => $time,
            'exp' => $time + $exp,
            'data' => array(
                'name' => $user
            )
        );

        $tokenrefresh = array(
            'iat' => $time,
            'exp' => $time + ( $exp * 2 ),
            'data' => array()
        );

        $jwt        = JWT::encode($token, $key);
        $jwtrefresh = JWT::encode($tokenrefresh, $key);

        header('Content-Type: application/json');
        echo "{\"access_token\":\"$jwt\",\"expires_in\":$exp,\"refresh_token\":\"$jwtrefresh\",\"token_type\":\"Bearer\"}";

        die();
    }
}
