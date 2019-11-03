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
  $Id: announcements.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class announcements extends rest {
    protected $table      = "announcement";
    protected $id_field   = 'announcement_id';
    protected $name_field = 'description';
    protected $extension_field = '';

    protected $category              = 'Announcements';
    protected $provides_destinations = true;

    protected $field_map = array(
        'recording_id'      => 'recording_id',
        'allow_skip'        => 'allow_skip',
        'post_dest'         => 'post_destination',
        'return_ivr'        => 'return_to_ivr',
        'noanswer'          => 'no_answer',
        'repeat_msg'        => 'repeat_key'
    );

    protected $transforms = array(
        'allow_skip'         => 'enabled',
        'return_ivr'         => 'enabled',
        'no_answer'          => 'enabled',
    );

    protected $presentationTransforms = array(
        'allow_skip'         => 'presentation_enabled',
        'return_to_ivr'      => 'presentation_enabled',
        'no_answer'          => 'presentation_enabled',
    );

    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val[$this->extension_field]:'s';
                $ret[$entity][]=array('name'=>$val['name'],'destination'=>'app-announcement-'.$val['id'].',s,1');
            }
        }
        return $ret;
    }

    protected function enabled($data) {
        if($data=='no') { return '0'; } else { return '1'; }
    }

    protected function presentation_enabled($data) {
        if($data=='0') { return 'no'; } else { return 'yes'; }
    }

}


