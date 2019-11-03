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
  $Id: allextensions.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class allextensions extends rest {

    protected $extensions = array();

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,1,0);

        $cdir = scandir('controllers/');
        $results=array();

        $obj = array();

        foreach ($cdir as $key => $controller) {
            if (!in_array($controller,array(".",".."))) {
                $controller = preg_replace("/\.php/","",$controller);
                if(!strstr($controller,'.') && $controller<>'allextensions' && !is_dir('controllers/'.$controller)) {
                    if($controller==$callingClass) { continue; } // do not create a new instance of calling class, it causes and endless loop
                    if($controller=='alldestinations' || $controller=='allextensions' || $controller=='musiconhold') { continue; }
                    $obj[$controller] = new $controller($f3);
                    if(is_callable(array($obj[$controller],'getExtensions'))) {
                        $ret = $obj[$controller]->getExtensions($f3);
                        if(count($ret)>0) { $this->extensions = array_merge($this->extensions,$ret); }
                    }
                }
            }
        }
        $this->extensions = array_unique($this->extensions);
        asort($this->extensions);
        $this->extensions=array_values($this->extensions);
    }

    public function get($f3, $from_child=0) {

        if(is_array($from_child)) {
            $this->outputSuccess($this->extensions);
        } else {
            return $this->extensions;
        }
    }

    public function search($f3, $from_child) {
        // searchs only on category, not on individual items
        $errors = array();
        if($f3->get('PARAMS.term')=='') {
            $errors[]=array('status'=>'405','detail'=>'Search term not provided');
            $this->dieWithErrors($errors);
        }

        $term = $f3->get('PARAMS.term');

        $res = $this->get($f3,1);

        $results = array();
        foreach($res as $idx=>$data) {
            // search only works by category in allextensions entity
            if(preg_match("/$term/i",$idx)) {
                $results[]=$data;
            }
        }
        // final json output
        $this->outputSuccess($results);
    }

    public function put($f3,$from_child) {
        $errors = array(array('status'=>'405','detail'=>'This resource is read only'));
        $this->dieWithErrors($errors);
    }

    public function post($f3,$from_child) {
        $errors = array(array('status'=>'405','detail'=>'This resource is read only'));
        $this->dieWithErrors($errors);
    }

    public function delete($f3,$from_child) {
        $errors = array(array('status'=>'405','detail'=>'This resource is read only'));
        $this->dieWithErrors($errors);
    }

}


