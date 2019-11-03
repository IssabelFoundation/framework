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
  $Id: inboundroutes.php, Tue 04 Sep 2018 09:55:16 AM EDT, nicolas@issabel.com
*/

class inboundroutes extends rest {
    protected $table      = "incoming";
    protected $id_field   = 'extension';
    protected $name_field = 'description';
    protected $extension_field = '';
    protected $list_fields = array('description','extension','destination','cidnum');
    protected $search_field    = 'description';
    protected $has_callrecording = 1;
    protected $has_cidlookup = 1;
    protected $valid_cid_lookups = array('0');

    // incoming table does not have a primary key, routes are identified by both extension and cidnum fields
    // so we have to make special conditions for loading data and use a special id using ^ as a separator
    // so the id field will be extension^cidnum
 
    protected $special_unique_condition = 'extension,cidnum';

    protected $sql_preparation_queries = array(
        'ALTER TABLE incoming ADD PRIMARY KEY (extension,cidnum)'
    );

    protected $field_map = array(
        'cidnum'        => 'callerid_number',
        'mohclass'      => 'music_on_hold_class',
        'destination'   => 'destination',
        'pricid'        => 'cid_priority_route',
        'alertinfo'     => 'alert_info',
        'grppre'        => 'cid_name_prefix',
        'ringing'       => 'signal_ringing',
        'delay_answer'  => 'pause_before_answer',
        'privacyman'    => 'privacy_manager.enabled',
        'pmmaxretries'  => 'privacy_manager.max_attempts',
        'pmminlength'   => 'privacy_manager.min_length',
        'language'      => 'language',
        'fax_detect.detection'     => 'fax_detect.detection_type',
        'fax_detect.detectionwait' => 'fax_detect.detection_time',
        'fax_detect.destination'   => 'fax_detect.destination'
    );

    protected $validations = array(
        'call_recording'          => array('never','force','delayed',''),
        'privacy_manager.enabled' => array('1','0'),
        'callerid_lookup'         => 'check_cidlookup'
    );

    protected $transforms = array(
        'privacy_manager.enabled'    => 'transformPrivacy',
        'cid_priority_route'         => 'checked',
    );

    protected $presentationTransforms = array(
        'privacy_manager.enabled'    => 'presentationTransformPrivacy',
        'cid_priority_route'         => 'presentation_checked',
    );

    protected function presentationTransformPrivacy($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }
    protected function transformPrivacy($data) {
        if($data=='yes') { return '1'; } else { return '0'; }
    }

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,0,1);

        // Check to see if callrecording module is installed
        $query = "DESC callrecording_module"; 
        try {
            $this->db->exec($query);
        } catch(Exception $e) {
            $this->has_callrecording = 0;
        }

        // Check to see if cid lookup module is installed
        $query = "DESC cidlookup_incoming"; 
        try {
            $this->db->exec($query);
            $query = "SELECT cidlookup_id FROM cidlookup";
            $rows = $this->db->exec($query);
            foreach($rows as $row) {
                $this->valid_cid_lookups[]=$row['cidlookup_id'];
            }
        } catch(Exception $e) {
            $this->has_cidlookup = 0;
        }
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $original_results = parent::get($f3,1);

        // Look for fax
        foreach($original_results as $idx=>$mainrow) {
            //unset($original_results[$idx]['route_id']);
            $query = "SELECT detection,detectionwait,destination FROM fax_incoming WHERE extension=? AND cidnum=?";
            $rows = $db->exec($query,array($mainrow['extension'],$mainrow['callerid_number']));
            foreach($rows as $idx2=>$data) {
                $rows[$idx2]['enabled']='yes';
                foreach($data as $key=>$val) {
                    $fkey = 'fax_detect.'.$key;
                    $finalkey = isset($this->field_map[$fkey])?$this->field_map[$fkey]:$key;
                    unset($rows[$idx2][$key]);
                    $finalkey = substr($finalkey,11);
                    $rows[$idx2][$finalkey]=$val;
                }
            }
            $original_results[$idx]['fax_detect']=count($rows)>0?$rows[0]:array('enabled'=>'no');
        }

        // Look for language
        foreach($original_results as $idx=>$mainrow) {
            $query = "SELECT language FROM language_incoming WHERE extension=? AND cidnum=?";
            $rows = $db->exec($query,array($mainrow['extension'],$mainrow['callerid_number']));
            if(count($rows)>0) {
                $original_results[$idx]['language']=$rows[0]['language'];
            } else {
                $original_results[$idx]['language']='';
            }
        }

        // Look for cidlookup
        if($this->has_cidlookup==1) {
            foreach($original_results as $idx=>$mainrow) {
                $query = "SELECT cidlookup_id FROM cidlookup_incoming WHERE extension=? AND cidnum=?";
                $rows = $db->exec($query,array($mainrow['extension'],$mainrow['callerid_number']));
                if(count($rows)>0) {
                    $original_results[$idx]['callerid_lookup']=$rows[0]['cidlookup_id'];
                } else {
                    $original_results[$idx]['callerid_lookup']='0';
                }
            }
        }

        // Call Recording
        if($this->has_callrecording==1) {
            foreach($original_results as $idx=>$mainrow) {
                $query = "SELECT callrecording FROM callrecording_module WHERE extension=? AND cidnum=? AND display='did'";
                $rows = $db->exec($query,array($mainrow['extension'],$mainrow['callerid_number']));
                if(count($rows)>0) {
                    $original_results[$idx]['call_recording']=$rows[0]['callrecording'];
                } else {
                    $original_results[$idx]['call_recording']='';
                }
            }
        }

        $this->outputSuccess($original_results);
    }

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

        //  $this->check_required_fields($f3,$input);

        $route_id = $f3->get('PARAMS.id');

        if(isset($input['fax_detect'])) {
            $this->set_fax($f3,$input,$route_id);
        }

        if(isset($input['language'])) {
            $this->set_language($f3,$input,$route_id);
        }

        if(isset($input['call_recording'])) {
            $this->set_callrecording($f3,$input,$route_id);
        }

        if(isset($input['callerid_lookup'])) {
            $this->set_cidlookup($f3,$input,$route_id);
        }

        $this->applyChanges($input);
    }

    function post($f3,$from_child) {

        $db = $f3->get('DB');

        $route_id = parent::post($f3,1);

        $input = $this->parseInputData($f3);
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

        if(isset($input['fax_detect'])) {
            $this->set_fax($f3,$input,$route_id);
        }

        if(isset($input['language'])) {
            $this->set_language($f3,$input,$route_id);
        }

        if(isset($input['call_recording'])) {
            $this->set_callrecording($f3,$input,$route_id);
        }

        if(isset($input['callerid_lookup'])) {
            $this->set_cidlookup($f3,$input,$route_id);
        }

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/$route_id", true, 201);
        die();

    }

    private function set_language($f3,$input,$route_id) {

        $db = $f3->get('DB');

        list($extension,$cidnum) = preg_split("/\^/",$route_id,2);

        $query = "DELETE FROM language_incoming WHERE extension=? and cidnum=?";
        $rows = $db->exec($query,array($extension,$cidnum));

        if($input['language']<>'') {
            $query = "INSERT INTO language_incoming (extension,cidnum,language) VALUES (?,?,?)";
            $rows = $db->exec($query,array($extension,$cidnum,$input['language']));
        }
    }

    private function set_callrecording($f3,$input,$route_id) {

        if($this->has_callrecording==0) { return; }

        $db = $f3->get('DB');

        list($extension,$cidnum) = preg_split("/\^/",$route_id,2);

        $query = "DELETE FROM callrecording_module WHERE extension=? and cidnum=?";
        $rows = $db->exec($query,array($extension,$cidnum));

        if($input['call_recording']<>'') {
            $query = "INSERT INTO callrecording_module (extension,cidnum,callrecording,display) VALUES (?,?,?,'did')";
            $rows = $db->exec($query,array($extension,$cidnum,$input['call_recording']));
        }
    }

    private function set_cidlookup($f3,$input,$route_id) {

        if($this->has_cidlookup==0) { return; }

        $db = $f3->get('DB');

        list($extension,$cidnum) = preg_split("/\^/",$route_id,2);

        $query = "DELETE FROM cidlookup_incoming WHERE extension=? and cidnum=?";
        $rows = $db->exec($query,array($extension,$cidnum));

        if(intval($input['callerid_lookup'])>0) {
            $query = "INSERT INTO cidlookup_incoming (extension,cidnum,cidlookup_id) VALUES (?,?,?)";
            $rows = $db->exec($query,array($extension,$cidnum,$input['callerid_lookup']));
        }
    }

    private function set_fax($f3,$input,$route_id) {

        $db = $f3->get('DB');

        $flatten_input = $this->flatten($input);

        $ENABLED=isset($flatten_input['fax_detect.enabled'])?$flatten_input['fax_detect.enabled']:'no';

        $defaults = array (
           'enabled'       => $ENABLED,
           'detection'     => 'dahdi',
           'detectionwait' => '3',
           'destination'   => ''
        );

        list($extension,$cidnum) = preg_split("/\^/",$route_id,2);

        $query = "SELECT detection,detectionwait,destination FROM fax_incoming WHERE extension=? and cidnum=?";
        $rows = $db->exec($query,array($extension,$cidnum));
        foreach($rows as $row) {
            $defaults['enabled']=isset($flatten_input['fax_detect.enabled'])?$flatten_input['fax_detect.enabled']:'yes';
            foreach($row as $key=>$val) {
                $defaults[$key]=$val;
            }
        }
        $query = "DELETE FROM fax_incoming WHERE extension=? AND cidnum=?";
        $rows = $db->exec($query,array($extension,$cidnum));

        if($defaults['enabled']=='yes') {

            $field_map_reverse = array_flip($this->field_map);
            foreach($flatten_input as $key=>$val) {
                if(array_key_exists($key,$field_map_reverse)) {
                    $finalkey = substr($field_map_reverse[$key],11);
                    $defaults[$finalkey]=$val;
                } 
            }
            $query = "INSERT INTO fax_incoming (cidnum,extension,detection,detectionwait,destination) VALUES (?,?,?,?,?)";
            $rows = $db->exec($query,array($cidnum,$extension,$defaults['detection'],$defaults['detectionwait'],$defaults['destination']));
        }

    }

    protected function check_cidlookup($data) {
        $errors = array();

        if($this->has_cidlookup==0) { return; }
        if(!in_array($data,$this->valid_cid_lookups)) {
            $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Invalid CID source ID');
            $this->dieWithErrors($errors);
        }
        return $data;
    }

    public function checked($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return 'CHECKED'; } else { return ''; }
    }

    public function presentation_checked($data) {
        if($data=='CHECKED') { return 'yes'; } else { return 'no'; }
    }

}

