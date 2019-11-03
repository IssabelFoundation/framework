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
  $Id: bosssecretary.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class bosssecretary extends rest {

    protected $table           = "bosssecretary_group";
    protected $id_field        = 'id_group';
    protected $name_field      = 'label';
    protected $extension_field = '';
    protected $list_fields     = array();
    protected $search_field    = 'label';
    protected $allextensions   = array();
    protected $required_fields = array('name','bosses.0','secretaries.0');

    protected $provides_destinations = false;

    protected $sql_preparation_queries = array(
        'ALTER TABLE bosssecretary_group DROP PRIMARY KEY',
        'ALTER TABLE bosssecretary_group CHANGE id_group id_group int(10) AUTO_INCREMENT PRIMARY KEY'
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,0,1);
        $alldest = new extensions($f3);
        $this->allextensions = $alldest->getExtensions($f3);
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {

            $secretaries = array();
            $bosses      = array();
            $chiefs      = array();
            $rews = $db->exec("SELECT secretary_extension FROM bosssecretary_secretary WHERE id_group=?", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $secretaries[]=$data2['secretary_extension'];
            }
            $rews = $db->exec("SELECT boss_extension FROM bosssecretary_boss WHERE id_group=?", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $bosses[]=$data2['boss_extension'];
            }
            $rews = $db->exec("SELECT chief_extension FROM bosssecretary_chief WHERE id_group=?", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $chiefs[]=$data2['chief_extension'];
            }
            $rows[$idx]['secretaries']=$secretaries;
            $rows[$idx]['bosses']=$bosses;
            $rows[$idx]['chiefs']=$chiefs;
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

        // die if any passed member is not a valid extension
        $check = array('bosses','secretaries','chiefs');
        foreach($check as $field) {
            if(isset($input[$field])) {
                if(is_array($input[$field])) {
                    foreach($input[$field] as $member) {
                        if(!in_array($member,$this->allextensions)) {
                            $errors[]=array('status'=>'422','source'=>$field, 'detail'=>$member.' is not a valid extension');
                        }
                    }
                } else {
                    $errors[]=array('status'=>'422','source'=>$field, 'detail'=>'Invalid type');
                }
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        // update main table
        parent::put($f3,1);

        $bosssecretaryid = $f3->get('PARAMS.id');

        // insert in group tables
        $already_inserted = array();
        $check = array('bosses'=>'bosssecretary_boss','secretaries'=>'bosssecretary_secretary','chiefs'=>'bosssecretary_chief');
        foreach($check as $field=>$table) {
            if(isset($input[$field])) {
                if(is_array($input[$field])) {
                    $query = "DELETE FROM $table WHERE id_group=?";
                    $db->exec($query,$bosssecretaryid);
                    foreach($input[$field] as $member) {
                        if(!in_array($member,$already_inserted)) {
                            $db->exec("INSERT INTO $table VALUES (?,?)",array($bosssecretaryid,$member));
                        }
                        $already_inserted[]=$member;
                    }
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

        $this->dieExtensionDuplicate($f3,$input['extension']);

        // die if any passed member is not a valid extension
        $check = array('bosses','secretaries','chiefs');
        foreach($check as $field) {
            if(isset($input[$field])) {
                if(is_array($input[$field])) {
                    foreach($input[$field] as $member) {
                        if(!in_array($member,$this->allextensions)) {
                            $errors[]=array('status'=>'422','source'=>$field, 'detail'=>$member.' is not a valid extension');
                        }
                    }
                } else {
                    $errors[]=array('status'=>'422','source'=>$field, 'detail'=>'Invalid type');
                }
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        // insert in main table 
        $bosssecretaryid = parent::post($f3,1);

        // insert in group tables
        $already_inserted = array();
        $check = array('bosses'=>'bosssecretary_boss','secretaries'=>'bosssecretary_secretary','chiefs'=>'bosssecretary_chief');
        foreach($check as $field=>$table) {
            if(isset($input[$field])) {
                if(is_array($input[$field])) {
                    $query = "DELETE FROM $table WHERE id_group=?";
                    $db->exec($query,$bosssecretaryid);
                    foreach($input[$field] as $member) {
                        if(!in_array($member,$already_inserted)) {
                            $db->exec("INSERT INTO $table VALUES (?,?)",array($bosssecretaryid,$member));
                        }
                    }
                } 
            }
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$bosssecretaryid, true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            $query = "DELETE FROM bosssecretary_secretary WHERE id_group=?";
            $db->exec($query,$oneid);
            $query = "DELETE FROM bosssecretary_boss WHERE id_group=?";
            $db->exec($query,$oneid);
            $query = "DELETE FROM bosssecretary_chief WHERE id_group=?";
            $db->exec($query,$oneid);
        }

        $this->applyChanges($input);

    }

}
