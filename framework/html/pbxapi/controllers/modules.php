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
  $Id: modules.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class modules extends rest {

    protected $table           = "modules";
    protected $id_field        = 'id';
    protected $name_field      = 'modulename';
    protected $extension_field = '';
    protected $search_field    = 'modulename';
    protected $list_fields     = array('version','enabled');
    protected $moduleInfo      = array();

    protected $field_map = array(
        'modulename' => 'module_name',
    );

    protected $transforms = array(
        'enabled' => 'enabled'
    );

    protected $presentationTransforms = array(
        'enabled' => 'presentation_enabled'
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,0,1);

        $query = "SELECT data FROM module_xml WHERE id='mod_serialized'";
        $rows = $this->db->exec($query,array($trunkid));
        $this->moduleInfo = unserialize($rows[0]['data']);
    }

    public function getModuleInfo($module='') {

        if($module=='') {
            return $this->moduleInfo;
        } else {
            if(array_key_exists($module,$this->moduleInfo)) {
                return $this->moduleInfo[$module];
            } else {
                return array();
            }
        }
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {
            $rawname = $data['rawname'];
            $rows[$idx]['full_name']=$this->moduleInfo[$data['name']]['name'];
        }

        $this->outputSuccess($rows);
    }

    protected function enabled($data) {
        if($data=='yes') { return '1'; } else { return '0'; }
    }

    protected function presentation_enabled($data) {
        if(intval($data)==1) { return 'yes'; } else { return 'no'; }
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
