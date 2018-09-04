<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
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
  $Id: ivroptions.php, Tue 04 Sep 2018 09:53:27 AM EDT, nicolas@issabel.com
*/

class ivroptions extends rest {

    protected $table           = "pbx_ivr_options";
    protected $name_field      = 'option';
    protected $dest_field      = 'dest';
    protected $search_field    = 'ivr_id';
    protected $extension_field = '';
    protected $list_fields  = array('pattern');

    public function ivr($f3) {

        // Almost exact same, but we want to search by ivr_id exactly, not likekly
        // So we can get with one request all options from a particular IVR

        if($f3->get('PARAMS.term')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        $this->condition = array($this->search_field.'=?',$f3->get('PARAMS.term'));
        $this->get($f3);

    }
}
