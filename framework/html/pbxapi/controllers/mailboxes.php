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
  $Id: mailboxes.php, Tue 04 Sep 2018 09:55:16 AM EDT, nicolas@issabel.com
*/

class mailboxes extends rest {
    protected $table      = "users";
    protected $id_field   = 'extension';
    protected $name_field = 'name';
    protected $extension_field = 'extension';
    protected $condition = array('voicemail=?','default');

    protected $provides_destinations = true;
    protected $context               = 'ext-local';
    protected $category              = 'Voicemail';

    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val['extension']:$val['id'];
                $ret[$entity][]=array('name'=>'<'.$ext.'> '.$val['name'].' (busy)', 'destination'=>$this->context.',vmb'.$ext.',1');
                $ret[$entity][]=array('name'=>'<'.$ext.'> '.$val['name'].' (unavail)', 'destination'=>$this->context.',vmu'.$ext.',1');
                $ret[$entity][]=array('name'=>'<'.$ext.'> '.$val['name'].' (no-msg)', 'destination'=>$this->context.',vms'.$ext.',1');
            }
        }
        return $ret;
    }
}

