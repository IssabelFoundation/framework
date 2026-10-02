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
  $Id: authorize.php, Tue 04 Sep 2018 09:55:56 AM EDT, nicolas@issabel.com
*/

use Firebase\JWT\JWT;

class authorize {

    protected $whitelist = array( '127.0.0.1', '::1');

    function authorized($f3) {

        $headers = $f3->get('HEADERS');

        if(!$f3->exists('HEADERS.Authorization')) {
            $this->unauthorized();
        }

        if(!preg_match('/^Bearer\s+([^\s]+)$/i', trim($headers['Authorization']), $matches)) {
            $this->unauthorized();
        }

        $jwt = $matches[1];
        $key = $f3->get('JWT_KEY');
        $data = null;
        $isMcp = false;

        JWT::$leeway = 60;

        if($f3->exists('JWT_MCP_PUBLIC_KEY')) {
            try {
                $data = JWT::decode($jwt, $f3->get('JWT_MCP_PUBLIC_KEY'), array('RS256'));
                $isMcp = true;
            } catch(Exception $e) {
                $data = null;
            }
        }

        if($data === null) {
            try {
                $data = JWT::decode($jwt, $key, array('HS256'));
            } catch(Exception $e) {
                $this->unauthorized();
            }
        }

        if($isMcp) {
            if(!isset($data->iss) || $data->iss !== 'issabel-mcp' ||
               !isset($data->sub) || trim($data->sub) === '' ||
               !isset($data->jti) || trim($data->jti) === '' ||
               !isset($data->iat) || !isset($data->exp) ||
               intval($data->iat) > time()+60 || intval($data->exp)-intval($data->iat) > 300 ||
               !$this->audienceContains($data, 'pbxapi')) {
                $this->unauthorized();
            }
            $scopes = $this->extractScopes($data);
            foreach($scopes as $scope) {
                if(!in_array($scope, array('extensions:read','extensions:plan','queues:read','queues:plan','ringgroups:read','ringgroups:plan','time:read','time:plan','ivr:read','ivr:plan','namespace:read','plans:read','plans:cancel'), true)) {
                    $this->forbidden();
                }
            }
            $this->enforceMcpEndpoint($f3, $scopes);
        } else {
            if(isset($data->type)) {
                if($data->type !== 'access' || !isset($data->iss) || $data->iss !== 'pbxapi' ||
                   !$this->audienceContains($data, 'pbxapi') || !isset($data->sub) || trim($data->sub) === '') {
                    $this->unauthorized();
                }
            } elseif(!isset($data->data->name) || trim($data->data->name) === '') {
                // Legacy access tokens contain data.name; legacy refresh tokens do not.
                $this->unauthorized();
            }
            // Existing valid administrator access tokens retain full API access.
            $scopes = array('*');
        }

        $f3->set('JWT_PAYLOAD', $data);
        $f3->set('JWT_SCOPES', $scopes);
        return $data;

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

    function requireScope($f3, $required) {
        $scopes = $f3->get('JWT_SCOPES');
        if(!is_array($scopes) ||
           (!in_array('*', $scopes, true) && !in_array($required, $scopes, true))) {
            header('Content-Type: application/json');
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
            header($protocol . ' 403 Forbidden', true, 403);
            echo json_encode(array('status'=>'forbidden','required_scope'=>$required));
            die();
        }
    }

    function requireAnyScope($f3, $required) {
        $scopes = $f3->get('JWT_SCOPES');
        if(is_array($scopes) && in_array('*', $scopes, true)) { return; }
        if(is_array($scopes)) {
            foreach($required as $scope) {
                if(in_array($scope, $scopes, true)) { return; }
            }
        }
        header('Content-Type: application/json');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol . ' 403 Forbidden', true, 403);
        echo json_encode(array('status'=>'forbidden','required_scope'=>$required));
        die();
    }

    protected function extractScopes($data) {
        if(!isset($data->scope)) {
            return array();
        }
        if(is_array($data->scope)) {
            return $data->scope;
        }
        if(is_string($data->scope)) {
            return preg_split('/\s+/', trim($data->scope), -1, PREG_SPLIT_NO_EMPTY);
        }
        return array();
    }

    protected function audienceContains($data, $expected) {
        if(!isset($data->aud)) {
            return false;
        }
        if(is_array($data->aud)) {
            return in_array($expected, $data->aud, true);
        }
        return $data->aud === $expected;
    }

    protected function enforceMcpEndpoint($f3, $scopes) {
        $controller = strtolower((string)$f3->get('PARAMS.controller'));
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        // RS256 service tokens are denied everywhere unless explicitly listed.
        // In particular they can never reach manager/originate, trunks, routes,
        // arbitrary controllers, or mutation methods on /extensions.
        if($controller === 'mcpextensions' && $method === 'GET' && in_array('extensions:read', $scopes, true)) {
            return;
        }
        if($controller === 'mcpqueues' && $method === 'GET' && in_array('queues:read', $scopes, true)) {
            return;
        }
        if($controller === 'mcpringgroups' && $method === 'GET' && in_array('ringgroups:read', $scopes, true)) {
            return;
        }
        if(($controller === 'mcptimegroups' || $controller === 'mcptimeconditions') && $method === 'GET' && in_array('time:read', $scopes, true)) {
            return;
        }
        if($controller === 'mcpivrs' && $method === 'GET' && in_array('ivr:read', $scopes, true)) {
            return;
        }
        if($controller === 'mcpnamespace' && $method === 'GET' && in_array('namespace:read', $scopes, true)) {
            return;
        }
        if($controller === 'mcpplans') {
            return; // The controller applies the operation-specific scope.
        }
        $this->forbidden();
    }

    protected function forbidden() {
        header('Content-Type: application/json');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol . ' 403 Forbidden', true, 403);
        echo json_encode(array('status'=>'forbidden'));
        die();
    }

    protected function unauthorized() {
        header('Content-Type: application/json');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
        header($protocol . ' 401 Unauthorized', true, 401);
        echo json_encode(array('status'=>'unauthorized'));
        die();
    }
}
