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
  $Id: setcallerid.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class setcallerid extends rest {

    protected $table           = "setcid";
    protected $id_field        = 'cid_id';
    protected $name_field      = 'description';
    protected $extension_field = '';
    protected $list_fields     = array('cid_num','cid_name','variables','dest');
    protected $search_field    = 'description';

    protected $provides_destinations = true;
    protected $context               = 'app-setcid';
    protected $category              = 'Set CallerID';

    protected $field_map = array(
        'cid_num'             => 'callerid_number',
        'cid_name'            => 'callerid_name',
        'dest'                => 'destination',
    );

    protected $transforms = array( 
    );

    protected $presentationTransforms = array( 
    );

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {
            if($data['variables']<>'') {
                $variables = array();
                $partes = preg_split("/,/",$data['variables']);
                foreach($partes as $entry) {
                    list ($var,$val) = preg_split("/=/",$entry);
                    $var = trim($var);
                    $val = trim($val);
                    $variables[]=array('name'=>$var,'value'=>$val);
                }
                unset($rows[$idx]['variables']);
                $rows[$idx]['variables']=$variables; 
            }
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    public function put($f3,$from_child) {

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

        // convert variable array to flat string for db storage
        if(isset($input['variables'])) {
            if(is_array($input['variables'])) {
                $vars = array();
                foreach($input['variables'] as $idx=>$data) {
                    $vars[]=$data['name'].'='.$data['value'];
                }
                $stringvars = implode(",",$vars);
                $input['variables']=$stringvars;
            }
        }

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
            $errors[]=array('status'=>'400','detail'=>$msg, 'code'=>$code);
            $this->dieWithErrors($errors);
        }

        if(is_array($from_child)) {
            $this->applyChanges($input);
        }
    }

    function post($f3,$from_child) {
        // INSERT record

        $errors = array();

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            $errors[]=array('status'=>'400','detail'=>'We refuse to insert a record if a resource id is passed. For update use the PUT method instead.');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);

        // convert variable array to flat string for db storage
        if(isset($input['variables'])) {
            if(is_array($input['variables'])) {
                $vars = array();
                foreach($input['variables'] as $idx=>$data) {
                    $vars[]=$data['name'].'='.$data['value'];
                }
                $stringvars = implode(",",$vars);
                $input['variables']=$stringvars;
            }
        }

        $input = $this->flatten($input);

        // Transform values passed if needed
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

        // Set default values if not passed via request, defaults uses the mapped/human readable field name
        $input = $this->setDefaults($f3,$input);

        // Set real table field names
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
            $this->data->save();

            if(isset($this->data->id)) {
                $mapid = $this->data->id;
            } else {
                $mapid = $this->data[$this->id_field];
            }

            if(is_array($from_child)) {

                $this->applyChanges($input);
                // 201 CREATED
                header("Location: $loc/$mapid", true, 201);
                die();

            } else {
                return $mapid;
            }

        } catch(\PDOException $e) {

            $err=$e->errorInfo;
            $msg = $e->getMessage();

            if ($e->getCode() != 23000) {
                // when trying to insert duplicate
                $errors[]=array('status'=>'409','detail'=>$msg);
            } else {
                // on other errors
                $errors[]=array('status'=>'400','detail'=>$msg);
            }
            die();
        }
    }

}


