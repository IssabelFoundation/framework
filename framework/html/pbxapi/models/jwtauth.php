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
            $data      = parse_conf("/etc/issabel.conf");
            $this->pwd = $data['amiadminpwd'];
        }

    }

    function parse_conf($file) {
        $result = array();
        $lines = file($file);
        foreach($lines as $line) {
            $partes = preg_split("/=/",$line);
            $result[trim($partes[0])]=trim($partes[1]);
        }
        return $result;
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
            if(isset($_SESSION['access_token']) && $_SESSION['access_token']<>'') {
                $jwt        = $_SESSION['access_token'];
                $jwtrefresh = isset($_SESSION['refresh_token']) ? $_SESSION['refresh_token'] : '';
                echo json_encode(array('access_token'=>$jwt,'refresh_token'=>$jwtrefresh,'token_type'=>'Bearer','status'=>'authorized'));
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
                JWT::$leeway = 60;
                $headers = $f3->get('HEADERS');
                if(!is_array($headers)) { $headers = array(); }
                $refreshToken = '';
                foreach($headers as $headerName=>$headerValue) {
                    if(strtolower(str_replace('_', '-', (string)$headerName)) === 'x-refresh-token' && $headerValue !== '') {
                        $refreshToken = (string)$headerValue;
                        break;
                    }
                }
                if($refreshToken === '') { $refreshToken = (string)$f3->get('GET.refresh_token'); }
                $data = JWT::decode($refreshToken, $key, array('HS256'));
                if(!isset($data->type) || $data->type !== 'refresh' ||
                   !isset($data->iss) || $data->iss !== 'pbxapi' ||
                   !isset($data->aud) || $data->aud !== 'pbxapi' ||
                   !isset($data->sub) || $data->sub === '') {
                    throw new RuntimeException('Invalid refresh token');
                }
                $tokens = $this->issueTokens($f3, (string)$data->sub);
                $tokens['status'] = 'authorized';
                echo json_encode($tokens);
                die();
            } catch(Exception $e) {
                echo json_encode(array('status'=>'unauthorized'));
                die();
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
        $input = json_decode($f3->get('BODY'),true);
        $user = isset($input['user'])?$input['user']:$f3->get('POST.user');
        if(!isset($user)) {
            $user = isset($input['username'])?$input['username']:$f3->get('POST.username');
        }
        $password    = isset($input['password'])?$input['password']: $f3->get('POST.password');
        $md5password = md5($password);

        //  $result = $this->db->exec('SELECT * FROM acl_user WHERE name = :name AND md5_password = :md5password',array(':name'=>$user,':md5password'=>$md5password));
        //  if($this->db->count() > 0) {

        if($user=='admin' && $password==$this->pwd) {

            header('Content-Type: application/json');
            echo json_encode($this->issueTokens($f3, $user));

            die();

        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
            die();
        }

    }

    protected function issueTokens($f3, $user) {
        $time = time();
        $key  = $f3->get('JWT_KEY');
        $exp  = $f3->get('JWT_EXPIRES');
        $jti  = $this->randomId();

        $token = array(
            'iss'=>'pbxapi', 'aud'=>'pbxapi', 'sub'=>$user, 'type'=>'access',
            'iat'=>$time, 'exp'=>$time+$exp, 'jti'=>$jti,
            'scope'=>array('*'), 'data'=>array('name'=>$user)
        );
        $refresh = array(
            'iss'=>'pbxapi', 'aud'=>'pbxapi', 'sub'=>$user, 'type'=>'refresh',
            'iat'=>$time, 'exp'=>$time+($exp*24), 'jti'=>$this->randomId()
        );
        return array(
            'access_token'=>JWT::encode($token, $key),
            'expires_in'=>$exp,
            'refresh_token'=>JWT::encode($refresh, $key),
            'token_type'=>'Bearer'
        );
    }

    protected function randomId() {
        if(function_exists('random_bytes')) { return bin2hex(random_bytes(16)); }
        $bytes = openssl_random_pseudo_bytes(16, $strong);
        if($bytes === false || !$strong) { throw new RuntimeException('Secure random source unavailable'); }
        return bin2hex($bytes);
    }
}
