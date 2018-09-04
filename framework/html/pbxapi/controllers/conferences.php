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
  $Id: conferences.php, Tue 04 Sep 2018 09:52:52 AM EDT, nicolas@issabel.com
*/

class conferences extends rest {
    protected $table           = 'meetme';
    protected $id_field        = 'exten';
    protected $extension_field = 'exten';
    protected $name_field      = 'description';
    protected $dest_field      = 'CONCAT("ext-meetme",",",exten,",1")';
    protected $search_field    = 'description';
    protected $initial_exten_n = '500';

    protected $field_map = array(
        'userpin'             => 'user_pin',
        'adminpin'            => 'admin_pin',
        'joinmsg_id'          => 'join_message_id',
        'users'               => 'max_participants'
    );

    protected $defaults = array(
        'admin_pin'        => '',
        'user_pin'         => '',
        'options'          => '',
        'music'            => 'inherit',
        'join_message_id'  => 0,
        'max_participants' => 10
    );
}


