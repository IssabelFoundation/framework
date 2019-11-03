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
  $Id: classofserviceadmin.php, Fri 05 Apr 2019 05:48:47 PM EDT, nicolas@issabel.com
*/

class classofserviceadmin extends rest {

    protected $table      = "customcontexts_contexts_list";
    protected $id_field   = 'context';
    protected $name_field = 'description';
    protected $extension_field = '';
    protected $list_fields  = array('description','locked');
    protected $required_fields = array('context');

    protected $transforms = array(
        'locked' => 'enabled',
    );

    protected $presentationTransforms = array(
        'locked' => 'presentation_enabled',
    );

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');

        $paramid = $f3->get('PARAMS.id');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {
            $contexts=array();
            $rews = $db->exec("SELECT include,description,sort FROM customcontexts_includes_list WHERE context=? ORDER BY sort", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $contexts[]=$data2;
            }
            $rows[$idx]['contexts']=$contexts;
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    function put($f3,$from_child) {

        // we need to remove contexts from put if any

        $errors = array();

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $this->data->load(array($this->id_field.'=?',$f3->get('PARAMS.id')));

        if ($this->data->dry()) {
            $errors[]=array('status'=>'404','detail'=>'Could not find a record to update');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);

        $this->update_contexts($f3,$input['contexts']);

        unset($input['contexts']);

        $input = $this->flatten($input);
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

        $field_map_reverse = array_flip($this->field_map);
        foreach($input as $key=>$val) {
            if(array_key_exists($key,$field_map_reverse)) {
                unset($input[$key]);
                $input[$field_map_reverse[$key]]=$val;
            }
        }

        $f3->set('INPUT',$input);

        try {
            $this->data->copyFrom('INPUT');
            $this->data->update();
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'400','detail'=>$msg,'code'=>$code);
            $this->dieWithErrors($errors);
        }

        if(is_array($from_child)) {
            $this->applyChanges($input);
        }

    }

    protected function update_contexts($f3,$contexts) {

        $db  = $f3->get('DB');
        if(!is_array($contexts)) {
            $errors[]=array('status'=>'422','source'=>'contexts', 'detail'=>'Invalid type');
            $this->dieWithErrors($errors);
        }
        foreach($contexts as $idx=>$data) {
            $db->exec("UPDATE customcontexts_includes_list SET description=?,sort=? WHERE include=?",array($data['description'],$data['sort'],$data['include']));
        }
    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }


}


