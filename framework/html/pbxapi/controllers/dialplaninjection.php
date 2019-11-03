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
  $Id: dialplaninjection.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class dialplaninjection extends rest {

    protected $table           = "dialplaninjection_dialplaninjections";
    protected $id_field        = 'id';
    protected $name_field      = 'description';
    protected $extension_field = 'exten';
    protected $list_fields     = array('destination');
    protected $search_field    = 'description';
    protected $required_fields = array('destination');

    protected $provides_destinations = true;
    protected $context               = 'injection-';
    protected $category              = 'Dialplan Injection';

    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val['extension']:$val['id'];
                $ret[$entity][]=array('name'=>'('.$ext.') '.$val['name'], 'destination'=>$this->context.$val['id'].',${EXTEN},1');
            }
        }
        return $ret;
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $paramid = $f3->get('PARAMS.id');

        if($paramid=='list') {
            $rows = $db->exec("SELECT description,command FROM dialplaninjection_commands_list"); 
            $this->outputSuccess($rows);
        }

        $rows = parent::get($f3,1);

        $commands=array();
        foreach($rows as $idx=>$data) {
            $extension_list = array();
            $rews = $db->exec("SELECT command FROM dialplaninjection_commands WHERE injectionid=? ORDER BY sort", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $commands[]=$data2['command'];
            }
            $rows[$idx]['commands']=$commands;
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    public function put($f3,$from_child) {

        $db  = $f3->get('DB');

        $input = $this->parseInputData($f3);

        // die if invalid type
        if(isset($input['commands'])) {
            if(!is_array($input['commmands'])) {
                $errors[]=array('status'=>'422','source'=>'commands', 'detail'=>'Invalid type');
            }
        }

        // update main table
        parent::put($f3,1);

        $dialplaninjectionid = $f3->get('PARAMS.id');

        // insert in commands table 
        if(isset($input['commands'])) {
            if(is_array($input['commands'])) {
                $query = "DELETE FROM dialplaninjection_commands WHERE injectionid=?";
                $db->exec($query,$dialplaninjectionid);
                $sort=1;
                foreach($input['commands'] as $cmd) {
                    $db->exec("INSERT INTO dialplaninjection_commands (injectionid,command,sort) VALUES (?,?,?)",array($dialplaninjectionid,$cmd,$sort));
                    $sort++;
                }
            }
        }

        $this->applyChanges($input);

    }

    public function post($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');
        $errors = array();

        $input = $this->parseInputData($f3);

        if($input['extension']<>'') {
            $this->dieExtensionDuplicate($f3,$input['extension']);
        }

        // die if any passed member is not a valid extension
        if(isset($input['extension_list'])) {
            if(is_array($input['extension_list'])) {
                foreach($input['extension_list'] as $member) {
                    if(!in_array($member,$this->allextensions)) {
                        $errors[]=array('status'=>'422','source'=>'extension_list', 'detail'=>$member.' is not a valid extension');
                    }
                }
                if(count($errors)>0) {
                    $this->dieWithErrors($errors);
                }
            }
        }

        // insert in main table 
        $dialplaninjectionid = parent::post($f3,1);

        // insert in commands table 
        if(isset($input['commands'])) {
            if(is_array($input['commands'])) {
                $query = "DELETE FROM dialplaninjection_commands WHERE injectionid=?";
                $db->exec($query,$dialplaninjectionid);
                $sort=1;
                foreach($input['commands'] as $cmd) {
                    $db->exec("INSERT INTO dialplaninjection_commands (injectionid,command,sort) VALUES (?,?,?)",array($dialplaninjectionid,$cmd,$sort));
                    $sort++;
                }
            }
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$dialplaninjectionid, true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            $query = "DELETE FROM dialplaninjection_commands WHERE injectionid=?";
            $db->exec($query,$oneid);
        }

        $this->applyChanges($input);

    }

}
