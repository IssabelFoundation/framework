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
  $Id: ringgroups.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class ringgroups extends rest {

    protected $table           = "ringgroups";
    protected $id_field        = 'grpnum';
    protected $name_field      = 'description';
    protected $extension_field = 'grpnum';
    protected $dest_field      = 'CONCAT("from-internal",",",grpnum,",1")';
    protected $list_fields     = array('grplist','strategy');
    protected $initial_exten_n = '600';
    protected $alldestinations = array();
    protected $search_field    = 'description';
    protected $allextensions   = array();
    protected $conn;
    protected $ami;

    protected $field_map = array(
        'changecid'             => 'change_callerid',
        'fixedcid'              => 'fixed_callerid',
        'grplist'               => 'extension_list',
        'grptime'               => 'ring_time',
        'grppre'                => 'cid_name_prefix',
        'annmsg_id'             => 'announce_id',
        'alertinfo'             => 'alert_info',
        'cfignore'              => 'ignore_call_forward_settings',
        'cwignore'              => 'skip_busy_agent',
        'cpickup'               => 'enable_call_pickup',
        'remotealert_id'        => 'remote_announce_id',
        'postdest'              => 'destination_if_no_answer',
        'ringing'               => 'music_on_hold_ringing',
        'needsconf'             => 'confirm_calls',
        'grpnum'                => 'extension',
        'description'           => 'name',
        'toolate_id'            => 'too_late_announce_id'
    );

    protected $transforms = array( 
        'extension_list'               => 'implode_array',
        'confirm_calls'                => 'checked',
        'enable_call_pickup'           => 'checked',
        'ignore_call_forward_settings' => 'checked',
        'skip_busy_agent'              => 'checked',
    );

    protected $presentation_transforms = array( 
        'extension_list'               => 'explode_array',
        'confirm_calls'                => 'presentation_checked',
        'enable_call_pickup'           => 'presentation_checked',
        'ignore_call_forward_settings' => 'presentation_checked',
        'skip_busy_agent'              => 'presentation_checked',
    );


    protected $validations = array(
        'strategy'  => array('ringall','ringall-prim','hunt','hunt-prim','memoryhunt','memoryhunt-prim','firstavailable','firstnotonphone'),
        'recording' => array('always','never','dontcare'),
        'ring_time' => 'is_less_than_300',
        'change_callerid' => array('default','fixed','extern','did','forcedid'),
    );

    protected $defaults   = array(
        'change_callerid'               =>  'default',
        'too_late_announce_id'          =>  0,
        'announce_id'                   =>  0,
        'cid_name_prefix'               =>  '',
        'ring_time'                     =>  20,
        'destination_if_no_answer'      =>  'app-blackhole,hangup,1',
        'alert_info'                    =>  '',
        'remote_announce_id'            =>  0,
        'confirm_calls'                 =>  '',
        'music_on_hold_ringing'         =>  'Ring',
        'ignore_call_forward_settings'  =>  '',
        'skip_busy_agent'               =>  '',
        'enable_call_pickup'            =>  '',
        'strategy'                      =>  'ringall'
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

        $rows = parent::get($f3,1);

        // Get ASTDB entries
        $res = $this->ami->DatabaseShow('RINGGROUP');
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            $astdb[$partes[3]][$partes[2]]=$val;
        }

        foreach($rows as $idx=>$data) {
            $rows[$idx]['change_callerid']=$astdb['changecid'][$data['extension']];
            $rows[$idx]['fixed_callerid']=$astdb['fixedcid'][$data['extension']];
        }

        // final json output
        $final = array();
        $final['results'] = $rows;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();
    }

    public function put($f3) {

        $db = $f3->get('DB');

        $input = $this->parse_input_data($f3);

        parent::put($f3,1);

        $ringgroup = $f3->get('PARAMS.id');

        $amidb = array();

        if(isset($input['change_callerid'])) {
            $amidb[] = "RINGGROUP/$ringgroup:changecid:${input['change_callerid']}";
        }

        if(isset($input['fixed_callerid'])) {
            $amidb[] = "RINGGROUP/$ringgroup:fixedcid:${input['fixed_callerid']}";
        }

        foreach($amidb as &$valor) {
            list ($family,$key,$value) = preg_split("/:/",$valor,3);
            $this->ami->DatabaseDel($family,$key);
            $this->ami->DatabasePut($family,$key,$value);
        }

        $this->applyChanges($input);

    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $input = $this->parse_input_data($f3);

        $this->check_required_fields($f3,$input);

        $ringgroup = parent::post($f3,1);

        // Set default values if not passed via request, defaults uses the mapped/human readable field name
        $input = $this->fill_with_defaults($f3,$input);

        if($ringgroup<>'') {
            $amidb = array(
                "RINGGROUP/$ringgroup:changecid:${input['change_callerid']}",
                "RINGGROUP/$ringgroup:fixedcid:${input['fixed_callerid']}",
            );
        } else {
            $amidb = array();
        }

        foreach($amidb as &$valor) {
            list ($family,$key,$value) = preg_split("/:/",$valor,3);
            $this->ami->DatabaseDel($family,$key);
            $this->ami->DatabasePut($family,$key,$value);
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$ringgroup, true, 201);
        die();

    }

    public function delete($f3) {

        $db  = $f3->get('DB');;

        // Because the users table in IssabelPBX does not have a primary key, we have to override
        // the rest class DELETE method and pass the condition as a filter

        if($f3->get('PARAMS.id')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            $this->data->load(array($this->id_field.'=?',$oneid));

            if ($this->data->dry()) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
                die();
            }

            // Delete from users table using SQL Mapper
            try {
                $this->data->erase($this->id_field."=".$oneid);
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

            // Delete all relevant ASTDB entries
            $this->ami->DatabaseDelTree('RINGGROUP/'.$oneid);
        }

        $this->applyChanges($input);
    }

    private function checkValidExtension($f3,$extension) {

        $db = $f3->get('DB');

        if(in_array($extension,$this->alldestinations)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict', true, 409);
            die();
        }

        // TODO: check valid extension range and no collision with other destinations
        return true;

    }

    private function check_required_fields($f3,$input) {

        // Required post fields
        if(!isset($input['extension_list'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

    }

    public function implode_array($data) {
        $return = implode("-",$data);
        return $return;
    }

    public function explode_array($data) {
        $return = explode("-",$data);
        return $return;
    }

    public function checked($data) {
        if($data==1 || $data=="1" || $data==strtolower("on")) { return 'CHECKED'; } else { return 'off'; }
    }

    public function presentation_checked($data) {
        if($data=='CHECKED') { return 'on'; } else { return 'off'; }
    }

    public function is_less_than_300($data) {
        if(!is_numeric($data)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        } else {
            if($data<1 || $data>300) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                die();
            }
        }
        return $data;
    }

}


