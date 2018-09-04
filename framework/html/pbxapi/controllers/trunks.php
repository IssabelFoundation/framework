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
  $Id: trunks.php, Tue 04 Sep 2018 09:54:59 AM EDT, nicolas@issabel.com
*/

class trunks extends rest {
    protected $table      = "trunks";
    protected $id_field   = 'trunkid';
    protected $name_field = 'name';
    protected $dest_field = "";
    protected $list_fields = array('tech','channelid');
    protected $extension_field = '';

    protected $field_map = array(
        'channelid'          => 'trunk_name',
        'usercontext'        => 'user_context',
        'maxchans'           => 'maximum_channels',
        'outcid'             => 'outbound_callerid',
        'dialoutprefix'      => 'dialout_prefix',
        'continue'           => 'continue_if_busy',
 

    );
}


