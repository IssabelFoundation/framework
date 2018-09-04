<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
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
  $Id: authorize.php, Tue 04 Sep 2018 09:55:56 AM EDT, nicolas@issabel.com
*/

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
