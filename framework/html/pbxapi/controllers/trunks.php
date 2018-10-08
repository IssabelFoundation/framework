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
  $Id: trunks.php, Mon 08 Oct 2018 04:17:37 PM EDT, nicolas@issabel.com
*/

class trunks extends rest {
    protected $table      = "trunks";
    protected $id_field   = 'trunkid';
    protected $name_field = 'name';
    protected $dest_field = "";
    protected $list_fields = array('tech','channelid');
    protected $extension_field = '';
    protected $ami;
    protected $conn;

    // callerid_options   (keepcid)
    // off   = Allow Any CID
    // on    = Block foreign CID
    // cnum  = Remove CNAM
    // all   = Force Trunk CID

    protected $field_map = array(
        'tech'               => 'technology',
        'channelid'          => 'channel_name',
        'usercontext'        => 'user_context',
        'maxchans'           => 'maximum_channels',
        'outcid'             => 'outbound_callerid',
        'keepcid'            => 'callerid_options',
        'dialoutprefix'      => 'dialout_prefix',
        'continue'           => 'continue_if_busy'
    );

    protected $defaults = array(
        'technology' => 'sip',
        'callerid_options' => 'off'
    );

    function __construct($f3) {

        $mgrpass     = $f3->get('MGRPASS');
        $this->ami   = new asteriskmanager();
        $this->conn  = $this->ami->connect("localhost","admin",$mgrpass);

        if(!$this->conn) {
           header($_SERVER['SERVER_PROTOCOL'] . ' 502 Service Unavailable', true, 502);
           die();
        }

        $this->db  = $f3->get('DB');

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');

        // If not authorized it will die out with 403 Forbidden
        $localauth = new authorize();
        $localauth->authorized($f3);

        try {
            $this->data = new DB\SQL\Mapper($this->db,$this->table);
            if($this->dest_field<>'') {
                $this->data->destination=$this->dest_field;
            }
        } catch(Exception $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        $rows = $this->db->exec("SELECT * FROM alldestinations");
        foreach($rows as $row) {
            $this->alldestinations[]=$row['extension'];
            if($row['type']=='extension') {
                $this->allextensions[]=$row['extension'];
            }
        }

    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $original_results = parent::get($f3,1);

        // get ASTDB trunk dial options
        $res = $this->ami->DatabaseShow('TRUNK');
        $astdb = array();
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            $astdb[$partes[3]][$partes[2]]=$val;
        }

        foreach($original_results as $idx=>$data) {
            // get sip trunk details
            $trunkid = $data['id'];
            if(isset($astdb['dialopts'][$trunkid])) {
                $original_results[$idx]['dial_options']=$astdb['dialopts'][$trunkid];
            }

            //get dial patters
            $query = "SELECT match_pattern_prefix,match_pattern_pass,prepend_digits,seq FROM trunk_dialpatterns WHERE trunkid=?";
            $rows = $db->exec($query,array($trunkid));
            foreach($rows as $idx2=>$datapattern) {
                foreach($datapattern as $key=>$val) {
                    $finalkey = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                    unset($rows[$idx2][$key]);
                    $rows[$idx2][$finalkey]=$val;
                }
                unset($rows[$idx2]['trunkid']);
            }
            $original_results[$idx]['patterns']=count($rows)>0?$rows:array();

            if($data['technology']=='sip') {
                $peer = array();
                $user = array();
                $register = '';
                $query = "SELECT * FROM sip WHERE id LIKE 'tr%-$trunkid'";
                $detrows = $this->db->exec($query);
                foreach($detrows as $row) {
                    if(preg_match("/^tr-peer/",$row['id'])) {
                        $peer[$row['keyword']]=$row['data'];
                    } else if (preg_match("/^tr-user/",$row['id'])) {
                        $user[$row['keyword']]=$row['data'];
                    } else if (preg_match("/^tr-reg/",$row['id'])) {
                        $register = $row['data'];
                    }
                }
                $original_results[$idx]['peer']=$peer;
                $original_results[$idx]['user']=$user;
                if($register<>'') { $original_results[$idx]['register']=$register; }
            }

        }

        // final json output
        $final = array();
        $final['results'] = $original_results;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $input = $this->parse_input_data($f3);

        $this->check_required_fields($f3,$input);

        $trunkid = parent::post($f3,1);

        if($trunkid==0) {
            // original fpbx table is badly designed and lacks an auto increment primary key, we must
            // change the trunkid field after default insertion to the next available number
            // the real fix will be up update the trunks table schema settings autoincrement 
            $query = "UPDATE trunks a, (SELECT COUNT(*) cnt FROM trunks) b SET a.trunkid=b.cnt WHERE a.trunkid=0";
            $db->exec($query);
            $rows = $this->db->exec("SELECT COUNT(*) cnt FROM trunks");
            $trunkid = $rows[0]['cnt'];
        }

        if(isset($input['patterns'])) {
            if(count($input['patterns'])>0) {
                $this->insert_patterns($f3,$input,$trunkid);
            }
        }

        $this->insert_user_peer($f3,$input,$trunkid);

        $amidb = array();

        if(isset($input['dial_options'])) {
            $amidb[] = "TRUNK/$trunkid:dialopts:${input['dial_options']}";
        }

        foreach($amidb as &$valor) {
            list ($family,$key,$value) = preg_split("/:/",$valor,3);
            $this->ami->DatabaseDel($family,$key);
            $this->ami->DatabasePut($family,$key,$value);
        }

        $this->applyChanges($input);

    }

    public function put($f3) {

        $db = $f3->get('DB');

        parent::put($f3);

        $input = $this->parse_input_data($f3);

        $this->check_required_fields($f3,$input);

        $trunkid = $f3->get('PARAMS.id');

        if(isset($input['patterns'])) {
            if(count($input['patterns'])>0) {
                $this->insert_patterns($f3,$input,$trunkid);
            }
        }

        $this->insert_user_peer($f3,$input,$trunkid);

        $amidb = array();

        if(isset($input['dial_options'])) {
            $amidb[] = "TRUNK/$trunkid:dialopts:${input['dial_options']}";
        }

        foreach($amidb as &$valor) {
            list ($family,$key,$value) = preg_split("/:/",$valor,3);
            $this->ami->DatabaseDel($family,$key);
            $this->ami->DatabasePut($family,$key,$value);
        }

        $this->applyChanges($input);
    }

    public function delete($f3) {

        $db = $f3->get('DB');

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);
        $cuantos = count($arrids);

        $repl    = str_repeat('?,',$cuantos);
        $repl    = substr($repl,0,-1);

        $query = "DELETE FROM trunk_dialpatterns WHERE trunkid IN ($repl)";

        try {
            $db->exec($query,$arrids);
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        foreach($arrids as $trunkid) {
            $this->ami->DatabaseDel("TRUNK/$trunkid", 'dialopts');
        }

        parent::delete($f3);
    }


    private function insert_user_peer($f3,$input,$trunkid) {

        $db = $f3->get('DB');

        if(!isset($input['technology'])) {
             $rows = $this->db->exec("SELECT tech FROM trunks WHERE trunkid=?",array($trunkid));
             $input['technology']=$rows[0]['tech'];
        }

        if($input['technology']<>'sip') { return; }

        if(isset($input['user'])) {
            $tid = "tr-user-".intval($trunkid);

            $db->exec("DELETE FROM sip WHERE id=?",array($tid));
            foreach($input['user'] as $key=>$val) {
                $query = "INSERT INTO sip (id,keyword,data) VALUES (?,?,?)";
                $db->exec($query,array($tid,$key,$val));
            }
        } 

        if(isset($input['peer'])) {
            $tid = "tr-peer-".intval($trunkid);
            $db->exec("DELETE FROM sip WHERE id=?",array($tid));
            foreach($input['peer'] as $key=>$vval) {
                $query = "INSERT INTO sip (id,keyword,data) VALUES (?,?,?)";
                $db->exec($query,array($tid,$key,$val));
            }
        }

        if(isset($input['register'])) {
            $tid = "tr-reg-".intval($trunkid);
            $db->exec("DELETE FROM sip WHERE id=?",array($tid));
            $db->exec("INSERT INTO sip (id,keyword,data) VALUES (?,?,?)",array($tid,'register',$input['register']));
        }
     
    }

    private function insert_patterns($f3,$input,$trunkid) {

        $db = $f3->get('DB');

        $defaults = array ( 
           'match_pattern_prefix' => '',
           'match_pattern_pass' => '',
           'prepend_digits' => '',
           'seq' =>  -1
        );

        $query = "DELETE FROM trunk_dialpatterns WHERE trunkid=?";
        $db->exec($query,array($trunkid));

        $cnt=1;
        foreach($input['patterns'] as $idx=>$data) {

            $fields = array();
            $vals   = array();
            $marks  = array();

            $fields[] = 'trunkid';
            $vals[]   = $trunkid;
            $marks[]  = '?';
            foreach($defaults as $key=>$val) {
                $final_key = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                $final_val = isset($data[$final_key])?$data[$final_key]:$val;

                if($key=='seq') { $final_val=$cnt; } 
                $fields[] = $key;
                $marks[]  = '?';
                $vals[]   = $final_val;

            }
            $query = "INSERT INTO trunk_dialpatterns (`".implode("`,`",$fields)."`) VALUES (".implode(",",$marks).")"; 
            $db->exec($query,$vals);
            $cnt++;
        }
    }

    private function check_required_fields($f3,$input) {

        $db = $f3->get('DB');

        if(!isset($input['channel_name'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        // reject patterns that are non numeric
        if(isset($input['patterns'])) {
            if(count($input['patterns'])>0) {
                $fields = array('prepend_digits');
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
}

