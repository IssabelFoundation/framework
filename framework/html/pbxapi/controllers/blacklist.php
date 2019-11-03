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
  $Id: blacklist.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class blacklist extends rest {

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,1,0);
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');
        $results = array();

        // Get ASTDB entries
        $res = $ami->DatabaseShow('blacklist');
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            $number       = $partes[2];
            $description  = $val;

            if($f3->get('PARAMS.id')<>'') {
                // individual entry
                if($number==$f3->get('PARAMS.id')) {
                    $results[] = array("number"=>$number,"description"=>$description);
                }
            } else {
                // whole collection
                $results[] = array("number"=>$number,"description"=>$description);
            }
        }

        $this->outputSuccess($results);
    }

    public function search($f3, $from_child=0) {

        $errors = array();
        $ami = $f3->get('AMI');

        if($f3->get('PARAMS.term')=='') {
            $errors[]=array('status'=>'405','detail'=>'Search term not provided');
            $this->dieWithErrors($errors);
        }

        $db = $f3->get('DB');
        $results = array();

        $term = $f3->get('PARAMS.term');

        // Get ASTDB entries
        $res = $ami->DatabaseShow('blacklist');
        foreach($res as $key=>$val) {
            $partes       = preg_split("/\//",$key);
            $number       = $partes[2];
            $description  = $val;
            if(strpos($number,$term) || strpos($description,$term)) {
                $results[] = array("number"=>$number,"description"=>$description);
            }
        }

        $this->outputSuccess($results);
    }
 
    public function put($f3,$from_child) {

        $errors = array();
        $db = $f3->get('DB');
        $ami = $f3->get('AMI');

        $input = $this->parseInputData($f3);

        if(!isset($input['description'])) {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $key = $f3->get('PARAMS.id');

        $value = $ami->DatabaseGet('blacklist',$key);

        if($value<>'') { 
            // Entry exists, remove it and add it with new description
            $ami->DatabaseDel('blacklist',$key);
            $ami->DatabasePut('blacklist',$key,$input['description']);
        }

    }

    public function post($f3, $from_child=0) {

        $errors = array();
        $ami = $f3->get('AMI');

        $input = $this->parseInputData($f3);

        $reqfields = array('description','number');
        foreach($reqfields as $field) {
            if(!isset($input[$field])) {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Required field missing');
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        $value = $ami->DatabaseGet('blacklist',$input['number']);

        if($value<>'') { 
            // duplicate
            $errors[]=array('status'=>'409','detail'=>'Number already exist');
            $this->dieWithErrors($errors);
        }

        $ami->DatabasePut('blacklist',$input['number'],$input['description']);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$input['number'], true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $db  = $f3->get('DB');;
        $ami = $f3->get('AMI');

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Cannot delete if no ID is supplied');
            $this->dieWithErrors($errors);
        }

        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error();
            $errors[]=array('status'=>'400','detail'=>'Could not decode JSON','code'=>$error);
            $this->dieWithErrors($errors);
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            // Delete all relevant ASTDB entries
            $ami->DatabaseDel('blacklist',$oneid);
        }

    }

    public function getExtensions($f3) {
        return array();
    }

    public function getDestinations($f3) {
        return array();
    }


}
