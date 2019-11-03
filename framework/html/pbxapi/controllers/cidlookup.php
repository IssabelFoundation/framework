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
  $Id: cidlookup.php, Fri 05 Apr 2019 05:48:47 PM EDT, nicolas@issabel.com
*/

class cidlookup extends rest {

    protected $table      = "cidlookup";
    protected $id_field   = 'cidlookup_id';
    protected $name_field = 'description';
    protected $extension_field = '';
    protected $list_fields  = array('description','sourcetype');

    protected $field_map = array (
        'sourcetype'           => 'source_type',
        'cache'                => 'cache',
        'deptname'             => 'department',
        'http_host'            => 'http.host',
        'http_port'            => 'http.port',
        'http_username'        => 'http.username',
        'http_password'        => 'http.password',
        'http_path'            => 'http.path',
        'http_query'           => 'http.query',
        'mysql_host'           => 'mysql.host',
        'mysql_dbname'         => 'mysql.dbname',
        'mysql_query'          => 'mysql.query',
        'mysql_username'       => 'mysql.username',
        'mysql_password'       => 'mysql.password',
        'mysql_charset'        => 'mysql.charset',
        'opencnam_account_sid' => 'opencnam.auth_token',
        'opencnam_auth_token'  => 'opencnam.account_sid'
    );




}


