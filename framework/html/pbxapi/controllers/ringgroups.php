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
  $Id: ringgroups.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class ringgroups extends rest {

    protected $table           = "ringgroups";
    protected $id_field        = 'grpnum';
    protected $name_field      = 'description';
    protected $extension_field = 'grpnum';
    protected $list_fields     = array('grplist','strategy');
    protected $initial_exten_n = '600';
    protected $alldestinations = array();
    protected $search_field    = 'description';

    protected $provides_destinations = true;
    protected $context               = 'ext-group';
    protected $category              = 'Ring Groups';

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

    protected $presentationTransforms = array( 
        'extension_list'               => 'explode_array',
        'confirm_calls'                => 'presentation_checked',
        'enable_call_pickup'           => 'presentation_checked',
        'ignore_call_forward_settings' => 'presentation_checked',
        'skip_busy_agent'              => 'presentation_checked',
    );


    protected $validations = array(
        'strategy'              => array('ringall','ringall-prim','hunt','hunt-prim','memoryhunt','memoryhunt-prim','firstavailable','firstnotonphone'),
        'recording'             => array('always','never','dontcare'),
        'ring_time'             => 'checkLess300',
        'change_callerid'       => array('default','fixed','extern','did','forcedid'),
        'music_on_hold_ringing' => array('Ring','default','none')
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

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,1,1); 
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $rows = parent::get($f3,1);

        // Get ASTDB entries
        $res = $ami->DatabaseShow('RINGGROUP');
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            $astdb[$partes[3]][$partes[2]]=$val;
        }

        foreach($rows as $idx=>$data) {
            $rows[$idx]['change_callerid']=$astdb['changecid'][$data['extension']];
            $rows[$idx]['fixed_callerid']=$astdb['fixedcid'][$data['extension']];
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    public function put($f3,$from_child) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $input = $this->parseInputData($f3);

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
            $ami->DatabaseDel($family,$key);
            $ami->DatabasePut($family,$key,$value);
        }

        $this->applyChanges($input);

    }

    public function post($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $input = $this->parseInputData($f3);

        $this->check_required_fields($f3,$input);

        $this->dieExtensionDuplicate($f3,$input['extension']);

        $ringgroup = parent::post($f3,1);

        // Set default values if not passed via request, defaults uses the mapped/human readable field name
        $input = $this->setDefaults($f3,$input);

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
            $ami->DatabaseDel($family,$key);
            $ami->DatabasePut($family,$key,$value);
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$ringgroup, true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $errors = array();
        $db  = $f3->get('DB');;
        $ami = $f3->get('AMI');;

        // Because the users table in IssabelPBX does not have a primary key, we have to override
        // the rest class DELETE method and pass the condition as a filter

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Cannot delete if no ID is supplied');
            $this->dieWithErrors($errors);
        }

        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error();
            $errors[]=array('status'=>'400','detail'=>'Could not decode JSON','code'=>$error);
            $this->dieWithErrors($errors);
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            $this->data->load(array($this->id_field.'=?',$oneid));

            if ($this->data->dry()) {
                $errors[]=array('status'=>'404','detail'=>'Could not find a record to delete');
                $this->dieWithErrors($errors);
            }

            // Delete from users table using SQL Mapper
            try {
                $this->data->erase($this->id_field."=".$oneid);
            } catch(\PDOException $e) {
                $msg  = $e->getMessage();
                $code = $e->getCode();
                $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                $this->dieWithErrors($errors);
            }

            // Delete all relevant ASTDB entries
            $ami->DatabaseDelTree('RINGGROUP/'.$oneid);
        }

        $this->applyChanges($input);
    }

    private function check_required_fields($f3,$input) {
        // Required post fields
        $errors = array();
        if(!isset($input['extension_list'])) {
            $errors[]=array('status'=>'422','source'=>'extension_list','detail'=>'Required field missing');
            $this->dieWithErrors($errors);
        }
    }

    protected function implode_array($data) {
        $return = implode("-",$data);
        return $return;
    }

    protected function explode_array($data) {
        $return = explode("-",$data);
        return $return;
    }

    protected function checked($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return 'CHECKED'; } else { return 'off'; }
    }

    protected function presentation_checked($data) {
        if($data=='CHECKED') { return 'yes'; } else { return 'no'; }
    }

    protected function checkLess300($data,$field,&$errors) {
        if(!is_numeric($data)) {
            $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Only numeric values allowed');
        } else {
            if($data<1 || $data>300) {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Valid range: 1-300');
            }
        }
        return $data;
    }

}


