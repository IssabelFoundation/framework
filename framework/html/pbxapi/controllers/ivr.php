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
  $Id: ivr.php, Tue 04 Sep 2018 09:53:35 AM EDT, nicolas@issabel.com
*/

class ivr extends rest {
    protected $table           = "ivr_details";
    protected $extension_field = '';

    protected $provides_destinations = true;
    protected $category              = 'IVR';

    protected $field_map = array(
        'description'             => 'description',
        'announcement'            => 'greet_recording',
        'directdial'              => 'direct_dial',
        'invalid_loops'           => 'invalid_retries',
        'invalid_retry_recording' => 'invalid_retry_recording',
        'invalid_destination'     => 'invalid_destination',
        'invalid_recording'       => 'invalid_recording',
        'retvm'                   => 'return_to_ivr_after_voicemail',
        'timeout_time'            => 'timeout',
        'timeout_recording'       => 'timeout_recording',
        'timeout_retry_recording' => 'timeout_retry_recording',
        'timeout_destination'     => 'timeout_destination',
        'timeout_loops'           => 'timeout_retries',
        'timeout_append_announce' => 'timeout_append_announcement',
        'invalid_append_announce' => 'invalid_append_announcement',
        'timeout_ivr_ret'         => 'return_on_timeout',
        'invalid_ivr_ret'         => 'return_on_invalid',
    );

    protected $transforms = array(
        'timeout_append_announcement'   => 'enabled',
        'invalid_append_announcement'   => 'enabled',
        'return_on_timeout'             => 'enabled',
        'return_on_invalid'             => 'enabled',
        'return_to_ivr_after_voicemail' => 'onoff',
        'direct_dial'                   => 'directdial'
    );
 
    protected $presentationTransforms = array(
        'timeout_append_announcement'   => 'presentation_enabled',
        'invalid_append_announcement'   => 'presentation_enabled',
        'return_on_timeout'             => 'presentation_enabled',
        'return_on_invalid'             => 'presentation_enabled',
        'return_to_ivr_after_voicemail' => 'presentation_enabled',
        'direct_dial'                   => 'presentation_directdial'
    );
 
    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val['extension']:$val['id'];
                $ret[$entity][]=array('name'=>$val['name'], 'destination'=>'ivr-'.$val['id'].',s,1');
            }
        }
        return $ret;
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $original_results = parent::get($f3,1);


        foreach($original_results as $idx=>$data) {
            $ivr_id = $data['id'];
            $query  = 'SELECT * FROM ivr_entries WHERE ivr_id=?';
            $rows = $this->db->exec($query,array($ivr_id));
            $entries = array();
            foreach($rows as $row) {
                $digit  = $row['selection'];
                $dest   = $row['dest'];
                $return = $row['ivr_ret'];
                if($return==1) { $return='yes'; } else { $return='no'; }
                $entries[$digit]=array('digits'=>$digit,'destination'=>$dest,'return_to_ivr'=>$return);
            }
            ksort($entries);
            $sorted_entries=array();
            foreach($entries as $idx2=>$data) {
                $sorted_entries[]=$data;
            }
            $original_results[$idx]['entries']=$sorted_entries;
        }

        if(is_array($from_child)) {
            $this->outputSuccess($original_results);
        } else {
            return $original_results;
        }
    }

    public function search($f3, $from_child) {

        if($f3->get('PARAMS.term')=='') {
            $errors[]=array('status'=>'405','detail'=>'Search term not provided');
            $this->dieWithErrors($errors);
        }

        $term = $f3->get('PARAMS.term');
        $res = $this->get($f3,1);
        $results = array();
        foreach($res as $idx=>$data) {
            if(preg_match("/$term/i",$data['name']) || preg_match("/$term/i",$data['description'])) {
                $results[]=$data;
            }
        }
        $this->outputSuccess($results);
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $input = $this->parseInputData($f3);

        $ivr_id = parent::post($f3,1);

        if(isset($input['entries'])) {
            foreach($input['entries'] as $idx=>$data) {
                $digits      = $data['digits'];
                $destination = $data['destination'];
                $return      = $data['return_to_ivr'];
                if($return=='yes') { $return=1; } else { $return=0; }
                $query = 'INSERT INTO ivr_entries (ivr_id,selection,dest,ivr_ret) VALUES (?,?,?,?)';
                $rows = $this->db->exec($query,array($ivr_id,$digits,$destination,$return));
            }
        }

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/".$ivr_id, true, 201);
        die();

    }

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);

        $ivr_id = $f3->get('PARAMS.id');

        if(isset($input['entries'])) {

            // first remove all entries
            $query  = 'DELETE FROM ivr_entries WHERE ivr_id=?';
            $rows = $this->db->exec($query,array($ivr_id));

            // then insert all passed entries
            foreach($input['entries'] as $idx=>$data) {
                $digits      = $data['digits'];
                $destination = $data['destination'];
                $return      = $data['return_to_ivr'];
                if($return=='yes') { $return=1; } else { $return=0; }
                $query = 'INSERT INTO ivr_entries (ivr_id,selection,dest,ivr_ret) VALUES (?,?,?,?)';
                $rows = $this->db->exec($query,array($ivr_id,$digits,$destination,$return));
            }
        }

        $this->applyChanges($input);
    }

    public function delete($f3, $from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);

        foreach($arrids as $ivr_id) {
            $query = "DELETE FROM ivr_entries WHERE ivr_id=?";
            $db->exec($query,array($ivr_id));
        }

        $this->applyChanges($input);

    }

    public function onoff($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return 'on'; } else { return 'off'; }
    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }

    public function directdial($data) {
        if($data=='yes') { return 'ext-local'; } else { return ''; }
    }

    public function presentation_directdial($data) {
        if($data=='ext-local') { return 'yes'; } else { return 'no'; }
    }


}
