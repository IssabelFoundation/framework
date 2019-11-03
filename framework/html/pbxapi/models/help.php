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
  $Id: help.php, Tue 04 Sep 2018 09:50:42 AM EDT, nicolas@issabel.com
*/

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
