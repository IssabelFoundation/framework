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
  $Id: outboundroutes.php, Tue 04 Sep 2018 09:54:24 AM EDT, nicolas@issabel.com
*/

class outboundroutes extends rest {
    protected $table           = "outbound_routes";
    protected $id_field        = 'route_id';
    protected $name_field      = 'name';
    protected $list_fields     = array('outcid','mohclass','dest','time_group_id');
    protected $extension_field = '';
    protected $has_callrecording = 1;
    protected $has_pinsets = 1;

    protected $field_map = array(
        'mohclass'           => 'music_on_hold_class',
        'outcid'             => 'outbound_callerid',
        'outcid_mode'        => 'outbound_callerid_mode',
        'match_cid'          => 'match_callerid',
        'seq'                => 'sequence',
        'dest'               => 'destination_if_congestion',
        'emergency_route'    => 'emergency_route',
        'intracompany_route' => 'intracompany_route',
        'password'           => 'password'
    );

    protected $transforms = array(
        'emergency_route'    => 'checked',
        'intracompany_route' => 'checked',
        'name' => 'no_spaces',
        'outbound_callerid_mode' => 'outcid'
    );

    protected $presentationTransforms = array(
        'emergency_route'        => 'presentation_checked',
        'intracompany_route'     => 'presentation_checked',
        'outbound_callerid_mode' => 'presentation_outcid'
    );

    protected $validations = array(
        'password'               => 'checkDigit',
        'outbound_callerid_mode' => array('override_extension',''),
        'call_recording'         => array('never','force','delayed',''),
    );

    protected $defaults = array(
        'outbound_callerid' => '',
        'music_on_hold_class' => 'default',
        'destination_if_congestion' => 'app-blackhole,hangup,1'
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,0,1);

        // Check to see if callrecording module is installed
        $query = "DESC callrecording_module";
        try {
            $this->db->exec($query);
        } catch(Exception $e) {
            $this->has_callrecording = 0;
        }

        // Check to see if pinset module is installed
        $query = "DESC pinset_usage";
        try {
            $this->db->exec($query);
        } catch(Exception $e) {
            $this->has_pinsets = 0;
        }
    }

    private function check_required_fields($f3,$input) {

        $errors = array();
        $db = $f3->get('DB');

        if(isset($input['trunks'])) {

            $all_trunks = $this->get_trunks($f3);

            foreach($input['trunks'] as $idx=>$data) {
                if(!isset($data['trunk_id'])) {
                    $errors[]=array('status'=>'422','source'=>'trunks.trunk_id','detail'=>'Required field missing');
                }  else {
                    if(!in_array($data['trunk_id'],$all_trunks)) {
                        $errors[]=array('status'=>'422','source'=>'trunks.trunk_id','detail'=>'Invalid trunk id');
                    }
                }
                if(!isset($data['sequence'])) {
                    $errors[]=array('status'=>'422','source'=>'trunks.sequence','detail'=>'Required field missing');
                }
            }
            if(count($errors)>0) {
                $this->dieWithErrors($errors);
            }
        }

        // reject patterns that are non numeric
        if(isset($input['patterns'])) {
            if(count($input['patterns'])>0) {
                $fields = array('match_callerid', 'prepend_digits');
                foreach($input['patterns'] as $idx=>$element) {
                    foreach($fields as $field) {
                        if(isset($input['patterns'][$idx][$field])) {
                            $without_digits = preg_replace("/[^0-9]/", "", $input['patterns'][$idx][$field]);
                            if($input['patterns'][$idx][$field]<>$without_digits) {
                                $errors[]=array('status'=>'422','source'=>'patterns.pattern','detail'=>'Incorrect format. Only digits allowed');
                            }
                        }
                    }
                }
                $fields = array('match_pattern_pass', 'match_pattern_prefix');
                foreach($input['patterns'] as $idx=>$element) {
                    foreach($fields as $field) {
                        if(isset($input['patterns'][$idx][$field])) {
                            $without_digits = preg_replace("/[^0-9]XZN\]\[\./i", "", $input['patterns'][$idx][$field]);
                            if($input['patterns'][$idx][$field]<>$without_digits) {
                                $errors[]=array('status'=>'422','source'=>'patterns.'.$field,'detail'=>'Incorrect format.');
                            }
                        }
                    }
                }
            }
        }
        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }
    }

    private function get_trunks($f3) {
        $db = $f3->get('DB');
        $query = "SELECT trunkid FROM trunks";
        $rows = $db->exec($query);
        $all_trunks_id = array();
        foreach($rows as $idx=>$data) {
           $all_trunks_ids[]=$data['trunkid'];
        }
        return $all_trunks_ids;
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $original_results = parent::get($f3,1);

        // Look for patterns
        foreach($original_results as $idx=>$mainrow) {
            $query = "SELECT * FROM outbound_route_patterns WHERE route_id=?";
            $rows = $db->exec($query,array($mainrow['id']));
            foreach($rows as $idx2=>$data) {
                foreach($data as $key=>$val) {
                    $finalkey = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                    unset($rows[$idx2][$key]);
                    $rows[$idx2][$finalkey]=$val;
                }
                unset($rows[$idx2]['route_id']);
            }
            $original_results[$idx]['patterns']=count($rows)>0?$rows:array();
        }

        // Look for trunks
        foreach($original_results as $idx=>$mainrow) {
            $query = "SELECT * FROM outbound_route_trunks WHERE route_id=? ORDER BY seq";
            $rows = $db->exec($query,array($mainrow['id']));
            foreach($rows as $idx2=>$data) {
                foreach($data as $key=>$val) {
                    $finalkey = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                    unset($rows[$idx2][$key]);
                    $rows[$idx2][$finalkey]=$val;
                }
                unset($rows[$idx2]['route_id']);
            }
            $original_results[$idx]['trunks']=count($rows)>0?$rows:array();
        }

        // Call Recording
        if($this->has_callrecording==1) {
            foreach($original_results as $idx=>$mainrow) {
                $query = "SELECT callrecording FROM callrecording_module WHERE extension=? AND display='routing'";
                $rows = $db->exec($query,array($mainrow['id']));
                if(count($rows)>0) {
                    $original_results[$idx]['call_recording']=$rows[0]['callrecording'];
                } else {
                    $original_results[$idx]['call_recording']='';
                }
            }
        }

        // Pinsets
        if($this->has_pinsets==1) {
            foreach($original_results as $idx=>$mainrow) {
                $query = "SELECT pinsets_id FROM pinset_usage WHERE foreign_id=? AND dispname='routing'";
                $rows = $db->exec($query,array($mainrow['id']));
                if(count($rows)>0) {
                    $original_results[$idx]['pinsets_id']=$rows[0]['pinsets_id'];
                } else {
                    $original_results[$idx]['pinsets_id']='0';
                }
            }
        }

        // Timegroups
        if($this->has_timegroups==1) {
            foreach($original_results as $idx=>$mainrow) {
                $query = "SELECT id FROM timegroups_groups WHERE id=? AND dispname='routing'";
                $rows = $db->exec($query,array($mainrow['id']));
                if(count($rows)>0) {
                    $original_results[$idx]['pinsets_id']=$rows[0]['pinsets_id'];
                } else {
                    $original_results[$idx]['pinsets_id']='0';
                }
            }
        }

        $this->outputSuccess($original_results);
    }

    public function delete($f3,$from_child) {

        $errors = array();
        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);
        $cuantos = count($arrids);

        $repl    = str_repeat('?,',$cuantos);
        $repl    = substr($repl,0,-1);

        $query = "DELETE FROM outbound_route_patterns WHERE route_id IN ($repl)";

        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
            $this->dieWithErrors($errors);
        }

        $query = "DELETE FROM outbound_route_trunks WHERE route_id IN ($repl)";
        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
            $this->dieWithErrors($errors);
        }

        $query = "DELETE FROM outbound_route_sequence WHERE route_id IN ($repl)";
        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
            $this->dieWithErrors($errors);
        }

        $this->applyChanges($input);

    }

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);

        $this->check_required_fields($f3,$input);

        $route_id = $f3->get('PARAMS.id');

        if(isset($input['patterns'])) {
            if(count($input['patterns'])>0) {
                $this->insert_patterns($f3,$input,$route_id);
            }
        }

        if(isset($input['trunks'])) {
            if(count($input['trunks'])>0) {
                $this->insert_trunks($f3,$input,$route_id);
            }
        }

        if(isset($input['pinsets_id'])) {
            $this->set_pinsets($f3,$input,$route_id);
        }

        if(isset($input['call_recording'])) {
            $this->set_callrecording($f3,$input,$route_id);
        }

        $this->applyChanges($input);
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $this->check_required_fields($f3,$input);

        $route_id = parent::post($f3,1);

        $input = $this->parseInputData($f3);

        $this->check_required_fields($f3,$input);

        if(isset($input['patterns'])) {
            if(count($input['patterns'])>0) {
                $this->insert_patterns($f3,$input,$route_id);
            }
        }

        if(isset($input['trunks'])) {
            if(count($input['trunks'])>0) {
                $this->insert_trunks($f3,$input,$route_id);
            }
        }

        if(isset($input['pinsets_id'])) {
            $this->set_pinsets($f3,$input,$route_id);
        }

        if(isset($input['call_recording'])) {
            $this->set_callrecording($f3,$input,$route_id);
        }

        $this->insert_sequence($f3,$route_id);

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/$route_id", true, 201);
        die();


    }

    private function set_pinsets($f3,$input,$route_id) {

        if($this->has_pinsets==0) { return; }

        $db = $f3->get('DB');

        $query = "DELETE FROM pinset_usage WHERE foreign_id=?";
        $rows = $db->exec($query,array($route_id));

        if(intval($input['pinsets_id'])>0) {
            $query = "INSERT INTO pinset_usage (pinsets_id,dispname,foreign_id) VALUES (?,'routing',?)";
            $rows = $db->exec($query,array($input['pinsets_id'],$route_id));
        }
    }

    private function set_callrecording($f3,$input,$route_id) {

        if($this->has_callrecording==0) { return; }

        $db = $f3->get('DB');

        $query = "DELETE FROM callrecording_module WHERE extension=?";
        $rows = $db->exec($query,array($route_id));

        if($input['call_recording']<>'') {
            $query = "INSERT INTO callrecording_module (extension,callrecording,display) VALUES (?,?,'routing')";
            $rows = $db->exec($query,array($route_id,$input['call_recording']));
        }
    }

    private function insert_sequence($f3,$route_id) {
        $db = $f3->get('DB');
        $query = "SELECT max(seq) AS seq FROM outbound_route_sequence";
        $row = $db->exec($query,array($route_id));

        $lastseq = $row[0]['seq'];

        if($lastseq=='') {
            $lastseq=0;
        } else {
             $lastseq = intval($lastseq);
             $lastseq = $lastseq+1;
        }

        $query = "INSERT INTO outbound_route_sequence VALUES(?,?)";
        $db->exec($query,array($route_id,$lastseq));
    }

    private function insert_patterns($f3,$input,$route_id) {

        $db = $f3->get('DB');

        $defaults = array (
           'match_pattern_prefix' => '',
           'match_pattern_pass' => '',
           'match_cid' => '',
           'prepend_digits' => ''
        );

        $query = "DELETE FROM outbound_route_patterns WHERE route_id=?";
        $db->exec($query,array($route_id));

        foreach($input['patterns'] as $idx=>$data) {

            $fields = array();
            $vals   = array();
            $marks  = array();

            $fields[] = 'route_id';
            $vals[]   = $route_id;
            $marks[]  = '?';
            foreach($defaults as $key=>$val) {
                $final_key = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                $final_val = isset($data[$final_key])?$data[$final_key]:$val;
                $fields[]=$key;
                $marks[]='?';
                $vals[]=$final_val;
            }
            $query = "INSERT INTO outbound_route_patterns (`".implode("`,`",$fields)."`) VALUES (".implode(",",$marks).")";
            $db->exec($query,$vals);
        }
    }

    private function insert_trunks($f3,$input,$route_id) {

        $db   = $f3->get('DB');

        $defaults = array (
           'trunk_id' => '1',
           'seq' => '1'
        );

        $query = "DELETE FROM outbound_route_trunks WHERE route_id=?";
        $db->exec($query,array($route_id));

        foreach($input['trunks'] as $idx=>$data) {

            $fields = array();
            $vals   = array();
            $marks  = array();

            $fields[] = 'route_id';
            $vals[]   = $route_id;
            $marks[]  = '?';
            foreach($defaults as $key=>$val) {
                $final_key = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                $final_val = isset($data[$final_key])?$data[$final_key]:$val;
                $fields[]=$key;
                $marks[]='?';
                $vals[]=$final_val;
            }
            $query = "INSERT INTO outbound_route_trunks (`".implode("`,`",$fields)."`) VALUES (".implode(",",$marks).")";
            $db->exec($query,$vals);
        }
    }

    public function checked($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data=='yes') { return 'YES'; } else { return 'off'; }
    }

    public function presentation_checked($data) {
        if($data=='YES' || $data='') { return 'yes'; } else { return 'no'; }
    }

    protected function checkDigit($data,$field,&$errors) {
        if(!preg_match("/^([0-9]*)$/",$data)) {
            $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Only digits allowed');
        }
        return $data;
    }

    public function no_spaces($data) {
        return preg_replace("/[^A-Za-z0-9_-]/", "", $data);
    }

    public function outcid($data) {
        if($data=='no') { return ''; } else { return 'override_extension'; }
    }

    public function presentation_outcid($data) {
        if($data=='') { return 'no'; } else { return 'yes'; }
    }

}

