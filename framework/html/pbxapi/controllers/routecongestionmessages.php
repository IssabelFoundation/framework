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
  $Id: routecongestionmessages.php, Tue 04 Sep 2018 09:53:01 AM EDT, nicolas@issabel.com
*/

class routecongestionmessages extends rest {
    protected $table      = "outroutemsg";
    protected $id_field   = 'keyword';
    protected $name_field = '';
    protected $extension_field ='';
    protected $recordings = array();

    protected $field_map = array(
       'default_msg_id'         => 'default_message',
       'intracompany_msg_id'    => 'intracompany_message',
       'emergency_msg_id'       => 'emergency_message',
       'unallocated_msg_id'     => 'unallocated_message',
       'no_answer_msg_id'       => 'no_answer_message',
       'invalidnmbr_msg_id'     => 'invalid_number_message'
    );

    protected $default_tones = array(
        -1 => 'DEFAULT',
        -2 => 'CONGESTION_TONE',
        -3 => 'INFO_TONE'
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,0,1);

        $recs = new recordings($f3);
        $recs->setGetAll(1);
        $res  = $recs->get($f3,1);
        $recs->setGetAll(0);

        foreach($res as $idx=>$data) {
            $this->recordings[$data['id']]=$data['id'];
        }
        $this->recordings['INFO_TONE']=-3;
        $this->recordings['CONGESTION_TONE']=-2;
        $this->recordings['DEFAULT']=-1;

    }

    function delete($f3,$from_child) {
        $errors[]=array('status'=>'405','detail'=>'This resource is read only');
        $this->dieWithErrors($errors);
    }

    function post($f3,$from_child) {
        $errors[]=array('status'=>'405','detail'=>'This resource is read only');
        $this->dieWithErrors($errors);
    }

    function get($f3,$from_child) {

        $db  = $f3->get('DB');

        $errors = array();

        $paramid = $f3->get('PARAMS.id');

        try {
            $rows = $db->exec("SELECT keyword,data FROM outroutemsg");
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'400','detail'=>$msg,'code'=>$code);
            $this->dieWithErrors($errors);
        }

        $result = array();

        if($db->count()==0) {
            foreach($this->field_map as $real=>$presentation) {
                $db->exec("INSERT INTO outroutemsg (keyword,data) VALUES (?,?)",array($real,-1));            
                $result[]=array('name'=>$presentation,'message'=>'DEFAULT');
            }
        }

        foreach($rows as $idx=>$data) {
            $finalkeyword = isset($this->field_map[$data['keyword']])?$this->field_map[$data['keyword']]:$data['keyword'];
            $finaldata = isset($this->default_tones[$data['data']])?$this->default_tones[$data['data']]:$data['data'];
            $result[]=array('name'=>$finalkeyword,'message'=>$finaldata);
        }

        if($paramid=='') {
            // collection
            $this->outputSuccess($result);
        } else {
            // record
            foreach($result as $idx=>$data) {
                if($data['name']==$paramid) {
                   $this->outputSuccess(array($data));
                } 
            }
            $this->outputSuccess(array());
        }
    }

    function put($f3,$from_child) {

        $db  = $f3->get('DB');

        $errors = array();

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);

        $reqfields = array('name','message');

        foreach($reqfields as $field) {
            if(!isset($input[$field])) {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Required field missing');
            }
        }

        if(!array_search($input['name'],$this->field_map)) {
            $errors[]=array('status'=>'422','source'=>'name','detail'=>'Invalid route type');
        }

        if(!array_search($input['message'],$this->recordings)) {
            $errors[]=array('status'=>'422','source'=>'message','detail'=>'Invalid message id');
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        $fmap = array_flip($this->field_map);
        $keyword = $fmap[$input['name']];

        $ftones = array_flip($this->default_tones);
        $tone   = isset($ftones[$input['message']])?$ftones[$input['message']]:$input['message'];

        $rows = $db->exec("SELECT * FROM outroutemsg WHERE keyword=?",array($keyword));
        if($db->count()==0) {
            $db->exec("INSERT INTO outroutemsg (keyword,data) VALUES (?,?)",array($keyword,$tone));
        } else {
            $db->exec("UPDATE outroutemsg SET data=? WHERE keyword=?",array($tone,$keyword));
        }

        $this->applyChanges($input);
    }

}


