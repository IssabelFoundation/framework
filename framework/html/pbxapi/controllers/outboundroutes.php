<?php

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

        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($original_results);
    }

    public function delete($f3) {

        $db = $f3->get('DB');

        parent::delete($f3);

        $allids = $f3->get('PARAMS.id');

        $query = "DELETE FROM outbound_route_patterns WHERE route_id IN (?)";
        try {
            $db->exec($query,array(1=>$allids));
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        $query = "DELETE FROM outbound_route_trunks WHERE route_id IN (?)";
        try {
            $db->exec($query,array(1=>$allids));
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

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

        $this->applyChanges($input);
 
    }

    private function insert_patterns($f3,$input,$route_id) {

        $db   = $f3->get('DB');

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

}

