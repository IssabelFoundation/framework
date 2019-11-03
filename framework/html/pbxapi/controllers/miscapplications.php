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
  $Id: miscapplications.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class miscapplications extends rest {

    protected $table           = "miscapps";
    protected $id_field        = 'miscapps_id';
    protected $name_field      = 'description';
    protected $search_field    = 'description';
    protected $extension_field = 'ext';
    protected $list_fields     = array('dest');

    protected $enabled         = array();

    protected $field_map  = array(
        'dest'      => 'destination',
        'enabled'   => 'enabled'
    );

    protected $transforms = array(
        'enabled' => 'enabled',
    );

    protected $presentationTransforms = array(
        'enabled' => 'presentation_enabled',
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,0,1);

        $query = "SELECT featurename,enabled FROM featurecodes WHERE modulename='miscapps'";
        $rows = $this->db->exec($query);
        foreach($rows as $row) {
            $this->enabled[$row['featurename']]=$row['enabled'];
        }
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $original_results = parent::get($f3,1);

        foreach($original_results as $idx=>$data) {
            $ena = isset($this->enabled['miscapp_'.$data['id']])?$this->enabled['miscapp_'.$data['id']]:0;
            $original_results[$idx]['enabled']=$ena;
            $original_results[$idx] = $this->presentationTransformValues($f3,$original_results[$idx]);
        }

        if(is_array($from_child)) {
            $this->outputSuccess($original_results);
        } else {
            return $original_results;
        }
    }

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);

        $appid = $f3->get('PARAMS.id');

        if(isset($input['enabled'])) {
            $ena = ($input['enabled']=='yes')?1:0;
        }

        $query = "UPDATE featurecodes SET enabled=? WHERE modulename='miscapps' AND featurename=?";
        $db->exec($query,array($ena,'miscapp_'.$appid));

        $this->applyChanges($input);
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $input = $this->parseInputData($f3);

        $appid = parent::post($f3,1);

        if(isset($input['enabled'])) {
            $ena = ($input['enabled']=='yes')?1:0;
        }

        $query = "INSERT INTO featurecodes (modulename,featurename,description,defaultcode,enabled,providedest) VALUES (?,?,?,?,?,?)";
        $db->exec($query,array('miscapps','miscapp_'.$appid,$input['name'],$input['extension'],$ena,0));

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/".$appid, true, 201);
        die();
    }

    public function delete($f3, $from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);

        foreach($arrids as $appid) {
            $query = "DELETE FROM featurecodes WHERE modulename='miscapps' AND featurename=?";
            $db->exec($query,array("miscapp_$appid"));
        }

        $this->applyChanges($input);
    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }


}


