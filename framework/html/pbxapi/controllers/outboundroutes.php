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
  $Id: outboundroutes.php, Tue 04 Sep 2018 09:54:24 AM EDT, nicolas@issabel.com
*/

class outboundroutes extends rest {
    protected $table           = "outbound_routes";
    protected $id_field        = 'route_id';
    protected $name_field      = 'name';
    protected $list_fields     = array('outcid','mohclass');
    protected $dest_field      = "dest";
    protected $extension_field = '';

    protected $field_map = array(
        'mohclass'    => 'music_on_hold_class',
        'outcid'      => 'outbound_callerid',
        'outcid_mode' => 'outbound_callerid_mode',
        'dest'        => 'destination',
        'match_cid'   => 'match_callerid',
        'seq'         => 'sequence'
    );

    protected $transforms = array(
        'emergency_route'    => 'checked',
        'intracompany_route' => 'checked',
        'name' => 'no_spaces',
    );

    protected $presentation_transforms = array(
        'emergency_route'    => 'presentation_checked',
        'intracompany_route' => 'presentation_checked',
    );

    protected $validations = array(
        'password' => 'only_digits',
        'outbound_callerid_mode' => array('override_extension',''),
    );

    protected $defaults = array(
        'outbound_callerid' => '',
        'music_on_hold_class' => 'default',
        'destination' => 'app-blackhole,hangup,1'
    );

    private function check_required_fields($f3,$input) {

        $db = $f3->get('DB');

        if(isset($input['trunks'])) {

            $all_trunks = $this->get_trunks($f3);

            foreach($input['trunks'] as $idx=>$data) {
                if(!isset($data['trunk_id'])) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                    die();
                }  else {
                    if(!in_array($data['trunk_id'],$all_trunks)) {
                        header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                        die();
                    }
                }
                if(!isset($data['sequence'])) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                    die();
                }
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
                                header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                                die();
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
                                header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                                die();
                            }
                        }
                    }
                }
 
            }
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
            $query = "SELECT * FROM outbound_route_trunks WHERE route_id=?";
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

        $final['results']=$original_results;

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
    }

    public function delete($f3) {

        $db = $f3->get('DB');


        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);
        $cuantos = count($arrids);


        $repl    = str_repeat('?,',$cuantos);
        $repl    = substr($repl,0,-1);
       
        $query = "DELETE FROM outbound_route_patterns WHERE route_id IN ($repl)";

        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        $query = "DELETE FROM outbound_route_trunks WHERE route_id IN ($repl)";
        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        $query = "DELETE FROM outbound_route_sequence WHERE route_id IN ($repl)";

        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        parent::delete($f3);
    }

    public function put($f3) {
        
        $db = $f3->get('DB');

        parent::put($f3);

        $input = $this->parse_input_data($f3);

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

        $this->applyChanges($input);
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $route_id = parent::post($f3,1);

        $input = $this->parse_input_data($f3);

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

        $this->insert_sequence($f3,$route_id);

        $this->applyChanges($input);
 
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
        if($data==1 || $data=="1" || $data==strtolower("on")) { return 'YES'; } else { return 'off'; }
    }

    public function presentation_checked($data) {
        if($data=='YES') { return 'on'; } else { return 'off'; }
    }

    public function only_digits($data) {
        return preg_replace("/[^0-9]/", "", $data);
    }

    public function no_spaces($data) {
        return preg_replace("/[^A-Za-z0-9_-]/", "", $data);
    }

}

