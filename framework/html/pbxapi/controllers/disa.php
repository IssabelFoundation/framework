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
  $Id: disa.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class disa extends rest {
    protected $table      = "disa";
    protected $id_field   = 'disa_id';
    protected $name_field = 'displayname';
    protected $extension_field = '';
    protected $list_fields  = array('pin','cid','context');
    protected $search_field = 'displayname';

    protected $category              = 'DISA';
    protected $provides_destinations = 'false';

    protected $field_map = array(
        'pin'          => 'pin',
        'cid'          => 'callerid',
        'context'      => 'context',
        'digittimeout' => 'digit_timeout',
        'resptimeout'  => 'response_timeout',
        'needconf'     => 'require_confirmation',
        'hangup'       => 'allow_hangup',
        'keepcid'      => 'callerid_override',
    );

    protected $transforms = array(
        'require_confirmation'                => 'checked',
        'allow_hangup'                        => 'checked',
        'callerid_override'                   => 'enabled',
    );

    protected $presentationTransforms = array(
        'require_confirmation'                => 'presentation_checked',
        'allow_hangup'                        => 'presentation_checked',
        'callerid_override'                   => 'presentation_enabled',
    );

    protected $defaults = array(
        'context'                             => 'from-internal'
    );

    public function checked($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return 'CHECKED'; } else { return 'off'; }
    }

    public function presentation_checked($data) {
        if($data=='CHECKED') { return 'yes'; } else { return 'no'; }
    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }


}


