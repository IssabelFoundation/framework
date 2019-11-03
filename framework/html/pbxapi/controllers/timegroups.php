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
  $Id: timegroups.php, Tue 04 Sep 2018 09:52:36 AM EDT, nicolas@issabel.com
*/

class timegroups extends rest {
    protected $table      = "timegroups_groups";
    protected $id_field   = 'id';
    protected $name_field = 'description';
    protected $extension_field = '';

    protected $provides_destinations = false;
    protected $category              = 'Time Groups';
    protected $required_fields = array('name');

    public function getDestinations($f3) {
        $ret = array();
        if($this->provides_destinations == true) {
            $res = $this->get($f3,1);
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                $ext = ($this->extension_field<>'')?$val['extension']:$val['id'];
                $ret[$entity][]=array('name'=>$val['name'], 'destination'=>$this->context.','.$ext.',1');
            }
        }
        return $ret;
    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {

            $rews = $db->exec("SELECT * FROM timegroups_details WHERE timegroupid=?", array($data['id']));
            $rows[$idx]['times']=array();
            foreach($rews as $idx2=>$data2) {
                list($hours,$weekdays,$days,$months) = preg_split("/\|/",$data2['time']);
                list($start_hour,$end_hour) = preg_split("/-/",$hours);
                list($start_weekday,$end_weekday) = preg_split("/-/",$weekdays);
                list($start_monthday,$end_monthday) = preg_split("/-/",$days);
                list($start_month,$end_month) = preg_split("/-/",$months);

                $rows[$idx]['times'][]= array(
                    'start_hour' => $start_hour,
                    'end_hour'   => isset($end_hour)?$end_hour:$start_hour,
                    'start_weekday' => $start_weekday,
                    'end_weekday'   => isset($end_weekday)?$end_weekday:$start_weekday,
                    'start_monthday' => $start_monthday,
                    'end_monthday'   => isset($end_monthday)?$end_monthday:$start_monthday,
                    'start_month' => $start_month,
                    'end_month'   => isset($end_month)?$end_month:$start_month
                );
            }
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    function post($f3,$from_child) {

        $db = $f3->get('DB');

        $errors=array();

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            $errors[]=array('status'=>'400','detail'=>'We refuse to insert a record if a resource id is passed. For update use the PUT method instead.');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);

        // convert variable array to flat string for db storage
        $alltimestrings=array();
        if(isset($input['times'])) {
            foreach($input['times'] as $idx=>$val) {
                $alltimestrings[] = $this->constructDateString($val);
            }
        }
        unset($input['times']);

        $input = $this->flatten($input);
        $this->checkRequiredFields($input);

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

            foreach($alltimestrings as $val) {
                $db->exec("INSERT INTO timegroups_details (timegroupid,time) VALUES (?,?)",array($mapid,$val));
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

            $err = $e->errorInfo;
            $msg = $e->getMessage();

            if ($e->getCode() != 23000) {
                // when trying to insert duplicate
                $errors[]=array('status'=>'409','detail'=>$msg);

            } else {
                // on other errors
                $errors[]=array('status'=>'400','detail'=>$msg);
            }
            $this->dieWithErrors($errors);
            die();
        }
    }

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);

        // convert variable array to flat string for db storage
        $alltimestrings=array();
        if(isset($input['times'])) {
            foreach($input['times'] as $idx=>$val) {
                $alltimestrings[] = $this->constructDateString($val);
            }
        }
        unset($input['times']);

        $groupid = $f3->get('PARAMS.id');

        $db->exec("DELETE FROM timegroups_details WHERE timegroupid=?",array($groupid));
        foreach($alltimestrings as $val) {
            $db->exec("INSERT INTO timegroups_details (timegroupid,time) VALUES (?,?)",array($groupid,$val));
        }

        $this->applyChanges($input);
    }

    public function delete($f3, $from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);

        foreach($arrids as $oneid) {
            $query = "DELETE FROM timegroups_details WHERE timegroupid=?";
            $db->exec($query,array($oneid));
        }

        $this->applyChanges($input);

    }

    private function constructDateString($data) {

        $errors   = array();
        $defaults = array();

        $defaults['start_hour']     = '00:00';
        $defaults['end_hour']       = '23:59';
        $defaults['start_weekday']  = '*';
        $defaults['end_weekday']    = '*';
        $defaults['start_monthday'] = '*';
        $defaults['end_monthday']   = '*';
        $defaults['start_month']    = '*';
        $defaults['end_month']      = '*';

        $validweekdays = array('*','mon','tue','wed','thu','fri','sat','sun');

        $finalval=array();
        foreach($defaults as $key=>$defaultval) {
            $finalval[$key] = isset($data[$key])?$data[$key]:$defaultval;

            if($key=='start_hour') {
                if(!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $finalval[$key])) {
                    $errors[]=array('status'=>'422','source'=>'start_hour','detail'=>'Incorrect format. Valid format: HH:MM');
                }
            } else if ($key=='end_hour') {
                if(!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $finalval[$key])) {
                    $errors[]=array('status'=>'422','source'=>'end_hour','detail'=>'Incorrect format. Valid format: HH:MM');
                }
            } else if ($key=='start_weekday') {
                if(!in_array($finalval[$key],$validweekdays)) {
                    $errors[]=array('status'=>'422','source'=>'start_weekday','detail'=>'Incorrect format. Allowed values: *,mon,tue,wed,thu,fri,sat,sun');
                }
            } else if ($key=='end_weekday') {
                if(!in_array($finalval[$key],$validweekdays)) {
                    $errors[]=array('status'=>'422','source'=>'end_weekday','detail'=>'Incorrect format. Allowed values: *,mon,tue,wed,thu,fri,sat,sun');
                }
            } else if ($key=='start_monthday') {
                if((intval($finalval[$key])<1 || intval($finalval[$key])>31) && $finalval[$key]<>'*') {
                    $errors[]=array('status'=>'422','source'=>'start_monthday','detail'=>'Incorrect format. Allowed values: 1-31 or *');
                }
            } else if ($key=='end_monthday') {
                if((intval($finalval[$key])<1 || intval($finalval[$key])>31) && $finalval[$key]<>'*') {
                    $errors[]=array('status'=>'422','source'=>'end_monthday','detail'=>'Incorrect format. Allowed values: 1-31 or *');
                }
            } else if ($key=='start_month') {
                if((intval($finalval[$key])<1 || intval($finalval[$key])>12) && $finalval[$key]<>'*') {
                    $errors[]=array('status'=>'422','source'=>'start_month','detail'=>'Incorrect format. Allowed values: 1-12 or *');
                }
            } else if ($key=='end_month') {
                if((intval($finalval[$key])<1 || intval($finalval[$key])>12) && $finalval[$key]<>'*') {
                    $errors[]=array('status'=>'422','source'=>'end_month','detail'=>'Incorrect format. Allowed values: 1-12');
                }
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        $finalparts=array();
        $finalparts[] = $finalval['start_hour'].'-'.$finalval['end_hour'];

        if($finalval['start_weekday']==$finalval['end_weekday']) { 
            $finalparts[] = $finalval['start_weekday'];
        } else {
            if($finalval['start_weekday']=='*' && $finalval['end_weekday']<>'*') {
                $finalval['start_weekday']=$finalval['end_weekday'];
            } else
            if($finalval['end_weekday']=='*' && $finalval['start_weekday']<>'*') {
                $finalval['end_weekday']=$finalval['start_weekday'];
            } 
            $finalparts[] = $finalval['start_weekday'].'-'.$finalval['end_weekday'];
        }
 
        if($finalval['start_monthday']==$finalval['end_monthday']) { 
            $finalparts[] = $finalval['start_monthday'];
        } else {
            if($finalval['start_monthday']=='*' && $finalval['end_monthday']<>'*') {
                $finalval['start_monthday']=$finalval['end_monthday'];
            } else
            if($finalval['end_monthday']=='*' && $finalval['start_monthday']<>'*') {
                $finalval['end_monthday']=$finalval['start_monthday'];
            } 
            $finalparts[] = $finalval['start_monthday'].'-'.$finalval['end_monthday'];
        }

        if($finalval['start_month']==$finalval['end_month']) { 
            $finalparts[] = $finalval['start_month'];
        } else {
            if($finalval['start_month']=='*' && $finalval['end_month']<>'*') {
                $finalval['start_month']=$finalval['end_month'];
            } else
            if($finalval['end_month']=='*' && $finalval['start_month']<>'*') {
                $finalval['end_month']=$finalval['start_month'];
            } 
            $finalparts[] = $finalval['start_month'].'-'.$finalval['end_month'];
        }

        $final = implode("|",$finalparts);
        return $final;

    }

}


