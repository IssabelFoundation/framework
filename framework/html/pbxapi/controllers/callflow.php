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
  $Id: callflow.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class callflow extends rest {
    protected $extension_field       = '';
    protected $name_fie              = 'name';
    protected $provides_destinations = true;
    protected $context               = 'app-daynight';
    protected $category              = 'Call Flow Control';

    protected $modes = array('DAY'=>'no','NIGHT'=>'yes');
    protected $featurecode = '*28';

    protected $field_map = array(
        'night'              => 'active_destination',
        'day'                => 'inactive_destination',
        'fc_description'     => 'name',
        'day_recording_id'   => 'inactive_recording_id',
        'night_recording_id' => 'active_recording_id',
        'password'           => 'password'
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,1,0);
        $this->db = $f3->get('DB');

        $query = "SELECT defaultcode,customcode FROM featurecodes WHERE modulename='daynight' and featurename='toggle-mode-all'";
        $rows = $this->db->exec($query);
        foreach($rows as $row) {
            $this->featurecode = ($row['customcode']<>'')?$row['customcode']:$row['defaultcode'];
        }
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');
        $ami = $f3->get('AMI');

        $results = array();
        $record  = array();
        // Get ASTDB entries
        $res = $ami->DatabaseShow('DAYNIGHT');
        foreach($res as $key=>$val) {
            $partes       = preg_split("/\//",$key);
            $slot         = substr($partes[2],1);
            $active       = $this->modes[$val];

            if($f3->get('PARAMS.id')<>'') {
                if($slot<>$f3->get('PARAMS.id')) { continue; }
            }

            $record['id']=$slot;
            $record['is_active']=$active;
            $query = "SELECT dmode,dest FROM daynight WHERE ext = ?";
            $rows = $this->db->exec($query,array($slot));
            foreach($rows as $row) {
               $field = $this->field_map[$row['dmode']];
               $record[$field]=$row['dest'];
            }
            if(!isset($record['password'])) { $record['password']=''; }
            $results[]=$record;
            $record=array();
        }

        if(is_array($from_child)) {
            $this->outputSuccess($results);
        } else {
            return $results;
        }
    }

    public function put($f3,$from_child) {

        $errors = array();
        $db     = $f3->get('DB');
        $ami = $f3->get('AMI');

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }
 
        $input = $this->parseInputData($f3);

        $slot = $f3->get('PARAMS.id');

        $saveval = array();
        $value = $ami->DatabaseGet('DAYNIGHT','C'.$slot);
        if($value<>'') { 
            // Entry exists, update table values
            $field_map_reverse = array_flip($this->field_map);
            foreach($input as $key=>$val) {
                if(array_key_exists($key,$field_map_reverse)) {
                    $saveval[$field_map_reverse[$key]]=$val;
                }
            }
        }
        foreach($saveval as $field=>$value) {
            if($field=='password') {
                if($value=='') {
                    $query = 'DELETE FROM daynight WHERE ext=? and dmode=?';
                    $db->exec($query,array($slot,$field));
                } else {
                    $query = "INSERT INTO daynight (ext,dmode,dest) VALUES (?,?,?)";
                    $db->exec($query,array($slot,$field,$value));
                }
            } else {
                $query = 'UPDATE daynight SET dest=? WHERE ext=? and dmode=?';
                $db->exec($query,array($value,$slot,$field));
            }
        }

        $rmode = array_flip($this->modes);
        if(isset($input['is_active'])) {
            $ena = isset($rmode[$input['is_active']])?$rmode[$input['is_active']]:'DAY';
        }
        $ami->DatabasePut('DAYNIGHT','C'.$slot,$ena);

    }

    public function post($f3, $from_child=0) {

        $errors = array();
        $db     = $f3->get('DB');
        $ami    = $f3->get('AMI');

        $input = $this->parseInputData($f3);

        $defaults = array('active_recording_id'=>0,'inactive_recording_id'=>0);

        $reqfields = array('name','id','active_destination','inactive_destination');
        foreach($reqfields as $field) {
            if(!isset($input[$field])) {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Required field missing');
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        if(intval($input['id'])<0 || intval($input['id'])>9) {
            $errors[]=array('status'=>'422','source'=>'id','detail'=>'Value out of range');
            $this->dieWithErrors($errors);
        }

        $value = $ami->DatabaseGet('DAYNIGHT','C'.$input['id']);
        if($value<>'') { 
            // duplicate
            $errors[]=array('status'=>'409','detail'=>'Duplicate ID');
            die();
        }

        $ena = 'DAY';
        if(isset($input['is_active'])) {
            if($input['is_active']=='yes') {
                $ena = 'NIGHT';
            }
        }

        $ami->DatabasePut('DAYNIGHT','C'.$input['id'],$ena);

        $field_map_reverse = array_flip($this->field_map);

        $val = array();
        foreach($field_map_reverse as $hfield=>$cfield) {
            $val[$cfield]=isset($input[$hfield])?$input[$hfield]:$defaults[$hfield];
        }

        $db->exec("DELETE FROM daynight WHERE ext=?",array($input['id']));
        foreach($val as $field=>$value) {
            $query = "INSERT INTO daynight (ext,dmode,dest) VALUES (?,?,?)";
            if($field=='password' and $value=='') {
                // do not insert password if empty
            } else {
                $db->exec($query,array($input['id'],$field,$value));
            }
        }

        $query = "INSERT INTO featurecodes (modulename,featurename,description,defaultcode,enabled,providedest) VALUES (?,?,?,?,?,?)";
        $db->exec($query,array('daynight','toggle-mode-'.$input['id'],$input['id'].": ".$input['name'],$this->featurecode.$input['id'],1,1));

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$input['id'], true, 201);
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

            $value = $ami->DatabaseGet('DAYNIGHT','C'.$oneid);
            if($value=='') { 
                $errors[]=array('status'=>'404','detail'=>'Could not find a record to delete');
                $this->dieWithErrors($errors);
            }

            // Delete all relevant ASTDB entries
            $ami->DatabaseDel('DAYNIGHT','C'.$oneid);

            $db->exec("DELETE FROM daynight WHERE ext=?",array($oneid));
            $db->exec("DELETE FROM featurecodes WHERE modulename='daynight' and featurename=?",array('toggle-mode-'.$oneid));

        }

    }

    public function search($f3, $from_child) {

        $errors = array();

        if($f3->get('PARAMS.term')=='') {
            $errors[]=array('status'=>'405','detail'=>'Search term not provided');
            $this->dieWithErrors($errors);
        }

        $term = $f3->get('PARAMS.term');
        $res = $this->get($f3,1);
        $results = array();
        foreach($res as $idx=>$data) {
            if(preg_match("/$term/i",$data['name'])) {
                $results[]=$data;
            }
        }
        $this->outputSuccess($results);
    }

}


