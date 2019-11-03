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
  $Id: featurecodes.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class featurecodes extends rest {

    protected $table      = "featurecodes";
    protected $id_field   = 'defaultcode';
    protected $name_field = 'featurename';
    protected $extension_field = 'defaultcode';
    protected $list_fields  = array('modulename','description','customcode','enabled','providedest');

    protected $provides_destinations = true;
    protected $context               = 'ext-featurecodes';
    protected $category              = 'Feature Codes';

    protected $field_map = array(
        'providedest' => 'provides_destination',
        'modulename'  => 'module_name',
        'featurename' => 'feature_name',
        'customcode'  => 'custom_code',
    );

    protected $special_unique_condition = 'modulename,featurename';

    protected $transforms = array(
        'enabled' => 'enabled'
    );

    protected $presentationTransforms = array(
        'enabled' => 'presentation_enabled'
    );

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $rows = parent::get($f3,1);

        $modules = new modules($f3);
        $modinfo = $modules->getModuleInfo();

        foreach($rows as $idx=>$data) {
            $rawname = $data['module_name'];
            $rows[$idx]['full_name']=$modinfo[$rawname]['name'];
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }
   
    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                if($val['provides_destination']==1) {
                    $ext = ($val['custom_code']<>'')?$val['custom_code']:$val['extension'];
                    ($this->extension_field<>'')?$val[$this->extension_field]:'s';
                    $ret[$entity][]=array('name'=>$val['description'].' <'.$ext.'>', 'destination'=>$this->context.",".$val['extension'].",1");
                }
            }
        }
        return $ret;
    }

    public function getExtensions($f3) {
        $ret = array();
        $this->get_all=1;
        $res = $this->get($f3,1);
        $this->get_all=0;
        foreach($res as $key=>$val) {
            $retval = ($val['custom_code']<>'')?$val['custom_code']:$val['extension'];
            $ret[] = $retval;
        }
        return $ret;
    }

    protected function enabled($data) {
        if($data=='yes') { return '1'; } else { return '0'; }
    }

    protected function presentation_enabled($data) {
        if(intval($data)==1) { return 'yes'; } else { return 'no'; }
    }

}


