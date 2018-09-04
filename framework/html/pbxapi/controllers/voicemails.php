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
  $Id: voicemails.php, Tue 04 Sep 2018 09:55:16 AM EDT, nicolas@issabel.com
*/

class voicemails extends rest {
    protected $table      = "users";
    protected $id_field   = 'extension';
    protected $name_field = 'name';
    protected $dest_field = 'CONCAT("from-internal",",","*",extension,",1")';
    protected $extension_field = 'extension';
    protected $condition = array('voicemail=?','default');

    function post($f3) {
    }

    function put($f3) {
    }

    function delete($f3) {
    }

    function search($f3) {
    }
}

