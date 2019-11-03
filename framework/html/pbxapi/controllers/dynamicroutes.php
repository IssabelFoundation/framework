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
  $Id: dynamicroutes.php, Tue 04 Sep 2018 09:53:35 AM EDT, nicolas@issabel.com
*/

class dynamicroutes extends rest {
    protected $table           = "dynroute";
    protected $extension_field = '';
    protected $name_field      = 'displayname';
    protected $id_field        = 'dynroute_id';
    protected $search_field    = 'displayname';

    protected $provides_destinations = true;
    protected $category              = 'Dynamic Routes';

    protected $field_map = array(
         'sourcetype'        => 'source_type',
         'chan_var_name'     => 'channel_var_name',
         'chan_var_name_res' => 'channel_var_name_result'
    );

    protected $transforms = array(
        'enable_dtmf_input' => 'checked'
    );

    protected $presentationTransforms = array(
        'enable_dtmf_input' => 'presentation_checked'
    );


    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val['extension']:$val['id'];
                $ret[$entity][]=array('name'=>$val['name'], 'destination'=>'dynroute-'.$val['id'].',s,1');
            }
        }
        return $ret;
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $original_results = parent::get($f3,1);

        $filtered_results = array();

        foreach($original_results as $idx=>$data) {
            
            $dynamicroutes_id = $data['id'];
            if($data['name']=='__install_done') {
                continue;
            }
            $query  = 'SELECT * FROM dynroute_dests WHERE dynroute_id=?';
            $rows = $this->db->exec($query,array($dynamicroutes_id));
            $entries = array();
            foreach($rows as $row) {
                $input   = $row['selection'];
                $dest    = $row['dest'];
                $default = $row['default_dest'];
                if($default=='y') { $default='yes'; } else { $default='no'; }
                $entries[]=array('input'=>$input,'destination'=>$dest,'default'=>$default);
            }
            $filtered_results[$idx]=$original_results[$idx];
            $filtered_results[$idx]['destinations']=$entries;
        }

        if(is_array($from_child)) {
            $this->outputSuccess(array_values($filtered_results));
        } else {
            return $filtered_results;
        }
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $input = $this->parseInputData($f3);

        $dynamicroutes_id = parent::post($f3,1);

        if(isset($input['destinations'])) {
            foreach($input['destinations'] as $idx=>$data) {
                $selection    = isset($data['input'])?$data['input']:'';
                $dest         = isset($data['destination'])?$data['destination']:'';
                $default      = isset($data['default'])?$data['default']:'';
                if($default=='yes') { $default='y'; } else { $default='n'; }
                $query = 'INSERT INTO dynroute_dests (dynroute_id,selection,dest,default_dest) VALUES (?,?,?,?)';
                $rows = $this->db->exec($query,array($dynamicroutes_id,$selection,$dest,$default));
            }
        }

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/".$dynamicroutes_id, true, 201);
        die();

    }

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);

        $dynamicroutes_id = $f3->get('PARAMS.id');

        if(isset($input['destinations'])) {

            // first remove all entries
            $query  = 'DELETE FROM dynroute_dests WHERE dynroute_id=?';
            $rows = $this->db->exec($query,array($dynamicroutes_id));

            // then insert all passed entries
            foreach($input['destinations'] as $idx=>$data) {
                $selection    = $data['input'];
                $dest         = $data['destination'];
                $default      = $data['default'];
                if($default=='yes') { $default='y'; } else { $default='n'; }
                $query = 'INSERT INTO dynroute_dests (dynroute_id,selection,dest,default_dest) VALUES (?,?,?,?)';
                $rows = $this->db->exec($query,array($dynamicroutes_id,$selection,$dest,$default));
            }
        }

        $this->applyChanges($input);
    }

    public function delete($f3, $from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);

        foreach($arrids as $dynamicroutes_id) {
            $query = "DELETE FROM dynroute_dests WHERE dynroute_id=?";
            $db->exec($query,array($dynamicroutes_id));
        }

        $this->applyChanges($input);

    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }

    protected function checked($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return 'CHECKED'; } else { return 'off'; }
    }

    protected function presentation_checked($data) {
        if($data=='CHECKED') { return 'yes'; } else { return 'no'; }
    }


}
