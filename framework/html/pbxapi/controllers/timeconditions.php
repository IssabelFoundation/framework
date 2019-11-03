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
  $Id: timeconditions.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class timeconditions extends rest {
    protected $table      = "timeconditions";
    protected $id_field   = 'timeconditions_id';
    protected $name_field = 'displayname';
    protected $extension_field = '';
    protected $required_fields = array('name','time_group_id','destination_if_time_does_not_match','destination_if_time_matches');

    protected $provides_destinations = true;
    protected $context               = 'timeconditions';
    protected $category              = 'Time Conditions';

    protected $field_map = array(
      'time'      => 'time_group_id',
      'truegoto'  => 'destination_if_time_matches',
      'falsegoto' => 'destination_if_time_does_not_match',
      'displayname' => 'name'
    );

    protected $validations = array(
        'override_state'         => array('','true','false','true_sticky','false_sticky'),
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,1,1);
    }

    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val['extension']:$val['id'];
                $ret[$entity][]=array('name'=>$val['name'], 'destination'=>$this->context.','.$ext.',1');
            }
        }
        return $ret;
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $rows = parent::get($f3,1);

        $astdb=array();
        // Get ASTDB entries
        $res = $ami->DatabaseShow('TC');
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            $astdb[$partes[2]]=$val;
        }

        foreach($rows as $idx=>$data) {
            $rows[$idx]['override_state']=isset($astdb[$data['id']])?$astdb[$data['id']]:'';
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    public function post($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $condid = parent::post($f3,1);

        $input = $this->parseInputData($f3);
        $input = $this->flatten($input);
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

        $amidb = array();

        if(!isset($input['override_state'])) {
            $input['override_state']='';
        }

        $ami->DatabaseDel('TC',$condid);
        $ami->DatabasePut('TC',$condid,$input['override_state']);

        $query = "INSERT INTO featurecodes (modulename,featurename,description,defaultcode,enabled,providedest) VALUES (?,?,?,?,?,?)";
        $db->exec($query,array('timeconditions','toggle-mode-'.$condid,$condid.': '.$input['name'],'*27'.$condid,1,1));

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/".$trunkid, true, 201);
        die();

    }

    public function put($f3,$from_child) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);
        $input = $this->validateValues($f3,$input);

        // we need to make sure only one record is marked as default
        $condid = $f3->get('PARAMS.id');

        if(isset($input['override_state'])) {
            $ami->DatabaseDel('TC',$condid);
            $ami->DatabasePut('TC',$condid,$input['override_state']);
        }

        $this->applyChanges($input);
    }

    public function delete($f3,$from_child) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        parent::delete($f3,1);

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);
        foreach($arrids as $oneid) {
            $ami->DatabaseDel("TC", $oneid);
            $query = "DELETE FROM featurecodes WHERE modulename=? AND featurename=?";
            $db->exec($query,array('timeconditions','toggle-mode-'.$oneid));
        }

        $this->applyChanges($input);

    }



}


