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
  $Id: systemrecordings.php, Tue 04 Sep 2018 09:53:01 AM EDT, nicolas@issabel.com
*/

class systemrecordings extends rest {

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,0,0);
    }

    function post($f3,$from_child) {
        $errors[]=array('status'=>'405','detail'=>'This resource is read only');
        $this->dieWithErrors($errors);
    }

    function put($f3,$from_child) {
        $errors[]=array('status'=>'405','detail'=>'This resource is read only');
        $this->dieWithErrors($errors);
    }

    function delete($f3,$from_child) {
        $errors[]=array('status'=>'405','detail'=>'This resource is read only');
        $this->dieWithErrors($errors);
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $sounddir = '/var/lib/asterisk/sounds';
        
        $rows  = array_values($this->getDirContents($sounddir,strlen($sounddir)+1));

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    private function getDirContents($dir, $strip, &$results = array()){
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if(!is_dir($path)) {
                $path = substr($path,$strip);
                if(substr($path,0,1)=='.') { continue; }
                if(!preg_match("/\.(au|g723|g723sf|g726-\d\d|g729|gsm|h263|ilbc|mp3|ogg|pcm|[au]law|[au]l|mu|sln|raw|vox|WAV|wav|wav49)/",$path)) { continue; }
                $path = preg_replace("/\.(au|g723|g723sf|g726-\d\d|g729|gsm|h263|ilbc|mp3|ogg|pcm|[au]law|[au]l|mu|sln|raw|vox|WAV|wav|wav49)$/", "", $path);
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                $this->getDirContents($path, $strip, $results);
            }
        }
        return $results;
    }

}


