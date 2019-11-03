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
  $Id: classofservice.php, Fri 05 Apr 2019 05:48:47 PM EDT, nicolas@issabel.com
*/

class classofservice extends rest {
    protected $table      = "customcontexts_contexts";
    protected $id_field   = 'context';
    protected $name_field = 'context';
    protected $extension_field = '';
    protected $list_fields  = array('context','description');
    
    protected $provides_destinations = true;
    protected $category              = 'Class of Service';
  
    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            $ret[$entity][]=array('name'=>'Full Internal Access', 'destination'=>'from-internal,${EXTEN},1');
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val[$this->extension_field]:'s';
                $ret[$entity][]=array('name'=>$val['name'], 'destination'=>$val['name'].',${EXTEN},1');
            }
        }
        return $ret;
    }

    protected $field_map =  array(
      'dialrules'              => 'dial_rules',
      'faildestination'        => 'destination.failover',
      'featurefaildestination' => 'destination.feature_code_failover',
      'failpin'                => 'destination.failover_pin',
      'featurefailpin'         => 'destination.feature_code_pin',
      'failpincdr'             => 'destination.fail_pin_cdr',
      'featurefailpincdr'      => 'destination.feature_code_fail_pin_cdr'
    );

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');

        $paramid = $f3->get('PARAMS.id');

        if($paramid=='list') {
//            $rows = $db->exec("SELECT * FROM customcontexts_includes_list ORDER BY sort");
            $rows = $db->exec("SELECT a.*,b.description AS category FROM customcontexts_includes_list a LEFT JOIN customcontexts_contexts_list b ON a.context=b.context ORDER BY b.description,a.include");
            $this->outputSuccess($rows);
        }

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {
            $contexts=array();
            $rews = $db->exec("SELECT include,timegroupid AS timegroup_id,userules as use_rules,sort FROM customcontexts_includes WHERE context=? ORDER BY sort", array($data['id']));
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

    public function put($f3,$from_child) {

        $db  = $f3->get('DB');

        $input = $this->parseInputData($f3);

        // update main table
        parent::put($f3,1);

        $cosid = $f3->get('PARAMS.id');

        // insert in customcontexts_includes table 
        if(isset($input['contexts'])) {
            if(is_array($input['contexts'])) {
                $query = "DELETE FROM customcontexts_includes WHERE context=?";
                $db->exec($query,$cosid);
                $already=array();
                foreach($input['contexts'] as $data) {
                    try {
                        if(!in_array($data['include'],$already)) {
                            $db->exec("INSERT INTO customcontexts_includes (context,include,timegroupid,userules,sort) VALUES (?,?,?,?,?)",array($cosid,$data['include'],$data['timegroup_id'],$data['use_rules'],$data['sort']));
                            $already[] = $data['include'];
                        }
                    } catch(\PDOException $e) {
                        $msg  = $e->getMessage();
                        $code = $e->getCode();
                        $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                        $this->dieWithErrors($errors);
                    }
                }
            }
        }

        $this->applyChanges($input);

    }

    public function post($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $errors = array();

        $input = $this->parseInputData($f3);

        // insert in main table 
        $cosid = parent::post($f3,1);

        // insert in customcontexts_includes table 
        if(isset($input['contexts'])) {
            if(is_array($input['contexts'])) {
                $query = "DELETE FROM customcontexts_includes WHERE context=?";
                $db->exec($query,$cosid);
                $already=array();
                foreach($input['contexts'] as $data) {
                    try {
                        if(!in_array($data['include'],$already)) {
                            $db->exec("INSERT INTO customcontexts_includes (context,include,timegroupid,userules,sort) VALUES (?,?,?,?,?)",array($cosid,$data['include'],$data['timegroup_id'],$data['use_rules'],$data['sort']));
                            $already[] = $data['include'];
                        }
                    } catch(\PDOException $e) {
                        $msg  = $e->getMessage();
                        $code = $e->getCode();
                        $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                        $this->dieWithErrors($errors);
                    }
                }
            }
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$dialplaninjectionid, true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            $query = "DELETE FROM customcontexts_includes WHERE context=?";
            $db->exec($query,$oneid);
        }

        $this->applyChanges($input);

    }

}
