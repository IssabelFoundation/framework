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
  $Id: rest.php, Fri 05 Apr 2019 06:06:16 PM EDT, nicolas@issabel.com
*/

class rest {

    protected $db;
    protected $data;
    protected $table = "";

    protected $id_field        = 'id';
    protected $name_field      = 'name';
    protected $extension_field = '';
    protected $search_field    = '';

    protected $condition                = null;
    protected $list_fields              = array();
    protected $field_map                = array();
    protected $initial_exten_n          = '200';
    protected $defaults                 = array();
    protected $transforms               = array();
    protected $presentationTransforms  = array();
    protected $validations              = array();
    protected $special_unique_condition = '';

    protected $context               = 'from-did-direct';
    protected $category              = '';
    protected $provides_destinations = false;

    protected $used_extensions       = array();
    protected $get_all               = 0;

    protected $module_installed      = true;

    protected $http_errors = array(
        '400'=>'400 Bad Request',
        '404'=>'404 Not Found',
        '405'=>'405 Method Not Allowed',
        '409'=>'409 Conflict',
        '422'=>'422 Unprocessabel Entity',
        '500'=>'500 Internal Server Error',
        '502'=>'502 Bad Gateway',
        '503'=>'503 Service Unavailable',
        '507'=>'507 Insufficient Storage'
     );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        $errors = array();
        $this->db = $f3->get('DB');

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');

        // If not authorized it will die out with 403 Forbidden
        $localauth = new authorize();
        $localauth->authorized($f3);

        if($sql_mapper==1) {

            $query = "DESC ".$this->table;
            try {
                $this->db->exec($query);
            } catch(\PDOException $e) {
                $this->module_installed = false;
            }

        }

        if($sql_mapper==1 && $this->module_installed == true) {
            // some tables needs to be modified, prepared before SQL mapper will work
            // like adding a primary key or an index
            if(isset($this->sql_preparation_queries)) {
                if(is_array($this->sql_preparation_queries)) { 
                    foreach($this->sql_preparation_queries as $query) {
                        try {
                            $this->db->exec($query);
                        } catch(Exception $e) {
                            // Ignore error 
                        }
                    }
                }
            }

            try {
                $this->data = new DB\SQL\Mapper($this->db,$this->table);
            } catch(Exception $e) {
                $msg  = $e->getMessage();
                $code = $e->getCode();
                $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                $this->dieWithErrors($errors);
            }

            if($this->extension_field<>'') {
                $this->field_map[$this->extension_field]='extension';
            }

            if($this->name_field<>'') {
                $this->field_map[$this->name_field]='name';
            }
        }

        if($ami_connect==1) {
            if(!$f3->exists('AMI')) {
                $mgrpass = $f3->get('MGRPASS');
                $ami = new asteriskmanager();
                $res = $ami->connect("localhost","admin",$mgrpass,'off',$errors);
                if(!$res) {
                    $this->dieWithErrors($errors);
                }
                $f3->set('AMI', $ami);
            }
        }

    }

    /**
     * Returns a single record or a collection from a REST GET request
     */
    function get($f3, $from_child) {

        $errors = array();

        if(!$this->module_installed) {
            $this->outputSuccess(array());
        }

        $paramid = $f3->get('PARAMS.id');

        // GET record or collection
        if($paramid=='' || $this->get_all==1) {
            // whole collection
            // we would never have a too big of collection, so store in memory for simplicity?

            $list = $this->data->find($this->condition);
            $results = array();

            foreach ($list as $obj) {

                $record    = array();
                $propid    = $this->id_field;
                $propname  = $this->name_field;
                $extenname = $this->extension_field;

                if($this->special_unique_condition<>'') {
                    $strcond=array();
                    $conditionfields = preg_split("/,/",$this->special_unique_condition);
                    foreach($conditionfields as $field) {
                        $strcond[] = $obj->$field;
                    }
                    $record['id'] = implode("^",$strcond);
                } else {
                    $record['id'] = $obj->$propid;
                }

                $record['name']   = $obj->$propname;
                if($extenname<>'') {
                    $record['extension']   = $obj->$extenname;
                }

                foreach ($this->list_fields as $extrafield) {
                    $val = $obj->$extrafield;
                    if(is_null($val)) { $val=''; }
                    $record[$extrafield] = $val;

                    if(isset($this->field_map[$extrafield])) {
                        unset($record[$extrafield]);
                        $record[$this->field_map[$extrafield]]=$val;
                    }
                }

                // Consider QUERY url fields as comma separated list of fields to show (besides default ones in controller)
                parse_str($f3->QUERY, $qparams);
                if(isset($qparams['fields'])) {

                    $otherfields=array();
                    if($qparams['fields']=='*') {
                        $allfields = $this->data->cast();
                        foreach($allfields as $key=>$val) {
                            $otherfields[]=isset($this->field_map[$key])?$this->field_map[$key]:$key; // stores human readable field name
                        }
                    } else {
                        $otherfields = $f3->split($qparams['fields']);
                    }

                    $field_map_reverse = array_flip($this->field_map);
                    foreach ($otherfields as $extrafield) {
                        $realfield = isset($field_map_reverse[$extrafield])?$field_map_reverse[$extrafield]:$extrafield;
                        if(isset($obj->$realfield)) {
                            $record[$extrafield] = $obj->$realfield;
                            if($extrafield<>$realfield) {
                                unset($record[$realfield]);
                            }
                        }
                    }
                }

                $record = $this->presentationTransformValues($f3,$record);
                $record = $this->unflatten($record);
                $results[]=$record;

            }

            // for security reasons we wrap results array into one object
            // https://www.owasp.org/index.php/AJAX_Security_Cheat_Sheet#Always_return_JSON_with_an_Object_on_the_outside

            if(is_array($from_child)) {
                $this->outputSuccess($results);
            } else {
               // return data to child class
               return $results;
            }

        } else {

            // individual record
            if($this->special_unique_condition <> '') {
                $strcond=array();
                $conditionfields = preg_split("/,/",$this->special_unique_condition);
                foreach($conditionfields as $field) {
                   $strcond[] = "$field=?";
                }

                $condparameters = preg_split("/\^/",$f3->get('PARAMS.id'));

                if(count($conditionfields)<>count($condparameters)) {
                    // if special condition id does not match the fields defined, return not found
                    $errors[]=array('status'=>'404','detail'=>'Combined ID count does not match resource definition. Required: '.count($conditionfields).', passed: '.count($condparameters));
                    $this->dieWithErrors($errors);
                }

                $this->data->load(array(implode(" AND ",$strcond),$condparameters));
            } else {
                $this->data->load(array($this->id_field.'=?',$f3->get('PARAMS.id')));
            }

            if ($this->data->dry()) {

                $errors[]=array('status'=>'404','detail'=>'Record not found');
                $this->dieWithErrors($errors);

            } else {

                $final = array();
                $final['results'] = $this->data->cast();

                $propid    = $this->id_field;
                $propname  = $this->name_field;
                $propexten = $this->extension_field;

                unset($final['results'][$propid]);
                unset($final['results'][$propname]);

                if($this->special_unique_condition<>'') {
                    $strcond=array();
                    $conditionfields = preg_split("/,/",$this->special_unique_condition);
                    foreach($conditionfields as $field) {
                        $strcond[] = $this->data->$field;
                    }
                    $final['results']['id'] = implode("^",$strcond);
                } else {
                    $final['results']['id'] = $this->data->$propid;
                }

                $final['results']['name'] = $this->data->$propname;

                if($propexten<>'') {
                    $final['results']['extension'] = $this->data->$propexten;
                }

                foreach($final['results'] as $key=>$val) {
                    if(isset($this->field_map[$key])) {
                        unset($final['results'][$key]);
                        $final['results'][$this->field_map[$key]]=$val;
                    }
                }
                $record = $this->presentationTransformValues($f3,$final['results']);
                $record = $this->unflatten($record);

                // even with 1 entry, we return an array for consistency
                $results = array($record);

                if(is_array($from_child)) {
                    $this->outputSuccess($results);
                } else {
                    return $results;
                }

            }
        }
    }

    /**
     * Inserts a new record into DB from a REST POST request
     */
    function post($f3,$from_child) {

        $errors = array();

        $db  = $f3->get('DB');

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            $errors[]=array('status'=>'400','detail'=>'We refuse to insert a record if a resource id is passed. For update use the PUT method instead.');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);
        $input = $this->flatten($input);
        $this->checkRequiredFields($input);

        // Get next extension number from proper table, filling gaps if any and only if extension was not specified
        $extenfield = $this->extension_field;
        if($extenfield<>'' && !isset($input['extension'])) {
            $query = "SELECT cast($extenfield AS unsigned)+1 AS extension FROM ".$this->table." mo WHERE NOT EXISTS ";
            $query.= "(SELECT NULL FROM ".$this->table." mi WHERE cast(mi.$extenfield AS unsigned) = CAST(mo.$extenfield AS unsigned)+ 1) ";
            $query.= "ORDER BY CAST($extenfield AS unsigned) LIMIT 1";
            $rows  = $this->db->exec($query);
            $EXTEN = $rows[0]['extension'];
            if($EXTEN=='') { $EXTEN=$this->initial_exten_n; }
            $input['extension'] = $EXTEN;
        }

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
        }
    }

    /**
     * Updates an existing record on DB from a REST PUT request
     */
    function put($f3,$from_child) {

        $errors = array();

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        if($this->special_unique_condition <> '') {
            $strcond=array();
            $conditionfields = preg_split("/,/",$this->special_unique_condition);
            foreach($conditionfields as $field) {
               $strcond[] = "$field=?";
            }

            $condparameters = preg_split("/\^/",$f3->get('PARAMS.id'));

            if(count($conditionfields)<>count($condparameters)) {
                // if special condition id does not match the fields defined, return not found
                $errors[]=array('status'=>'404','detail'=>'Combined ID count does not match resource definition. Required: '.count($conditionfields).', passed: '.count($condparameters));
                $this->dieWithErrors($errors);
            }

            $this->data->load(array(implode(" AND ",$strcond),$condparameters));

        } else {
            $this->data->load(array($this->id_field.'=?',$f3->get('PARAMS.id')));
        }

        if ($this->data->dry()) {
            $errors[]=array('status'=>'404','detail'=>'Could not find a record to update');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);
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

    /**
     * Delete one or more records from DB from a REST DELETE request
     */
    function delete($f3,$from_child) {

        $errors=array();

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Cannot delete if no ID is supplied');
            $this->dieWithErrors($errors);
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            if($this->special_unique_condition <> '') {
                $strcond=array();
                $conditionfields = preg_split("/,/",$this->special_unique_condition);
                foreach($conditionfields as $field) {
                   $strcond[] = "$field=?";
                }

                $condparameters = preg_split("/\^/",$oneid);

                if(count($conditionfields)<>count($condparameters)) {
                    // if special condition id does not match the fields defined, return not found
                    $errors[]=array('status'=>'404','detail'=>'Combined ID count does not match resource definition. Required: '.count($conditionfields).', passed: '.count($condparameters));
                    $this->dieWithErrors($errors);
                }

                $this->data->load(array(implode(" AND ",$strcond),$condparameters));

            } else {
                $this->data->load(array($this->id_field.'=?',$oneid));
            }

            if ($this->data->dry()) {
                $errors[]=array('status'=>'404','detail'=>'Could not find a record to delete');
                $this->dieWithErrors($errors);
            }

            try {
                $this->data->erase();
            } catch(\PDOException $e) {

                $query = "DELETE FROM ".$this->table." WHERE `".$this->id_field."`=?";
                try {
                    $this->db->exec($query,array($oneid));
                } catch(\PDOException $e) {
                    $msg  = $e->getMessage();
                    $code = $e->getCode();
                    $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                    $this->dieWithErrors($errors);
                }
            }

        }

        if(!is_array($from_child)) {
           $this->applyChanges($input);
        }
    }

    public function search($f3, $from_child) {

        $errors=array();

        if($f3->get('PARAMS.term')=='') {
            $errors[]=array('status'=>'405','detail'=>'Search term not provided');
            $this->dieWithErrors($errors);
        }

        if($this->search_field=='') { $this->search_field = $this->name_field; }

        $list = $this->data->find(array($this->search_field.' LIKE ?',"%".$f3->get('PARAMS.term')."%"));

        $results = array();

        foreach ($list as $obj) {
            $record = array();

            $propid    = $this->id_field;
            $propname  = $this->name_field;
            $extenname = $this->extension_field;

            $record['id']          = $obj->$propid;
            $record['name']        = $obj->$propname;
            if($extenname<>'') {
                $record['extension']   = $obj->$extenname;
            }
            if(isset($obj->destination)) {
                $record['destination'] = $obj->destination;
            }

            // Consider QUERY url fields as comma separated list of fields to show (besides default ones in controller)

            parse_str($f3->QUERY, $qparams);
            if(isset($qparams['fields'])) {

                $otherfields=array();
                if($qparams['fields']=='*') {
                    $allfields = $this->data->cast();
                    foreach($allfields as $key=>$val) {
                        $otherfields[]=isset($this->field_map[$key])?$this->field_map[$key]:$key; // stores human readable field name
                    }
                } else {
                    $otherfields = $f3->split($qparams['fields']);
                }

                $field_map_reverse = array_flip($this->field_map);
                foreach ($otherfields as $extrafield) {
                    $realfield = isset($field_map_reverse[$extrafield])?$field_map_reverse[$extrafield]:$extrafield;
                    if(isset($obj->$realfield)) {
                        $record[$extrafield] = $obj->$realfield;
                        if($extrafield<>$realfield) {
                            unset($record[$realfield]);
                        }
                    }
                }
            }

            $record = $this->presentationTransformValues($f3,$record);

            $results[]=$record;
        }

        // for security reasons we wrap results array into one object
        // https://www.owasp.org/index.php/AJAX_Security_Cheat_Sheet#Always_return_JSON_with_an_Object_on_the_outside

        if(is_array($from_child)) {
            $this->outputSuccess($results);
        } else {
            return $results;
        }
    }

    /**
     * Returns an array with all possible destinations (context,extension,priority)
     * including name and extension number if appropriate
     */
    public function getDestinations($f3) {
        $ret = array();

        if(!$this->module_installed) {
            return $ret;
        }

        if($this->provides_destinations == true) {
            $this->get_all=1;
            $res = $this->get($f3,1);
            $this->get_all=0;
            $entity = ($this->category<>'')?$this->category:get_class($this);
            foreach($res as $key=>$val) {
                if($this->extension_field<>'') {
                    $ext = $val['extension'];
                    $ret[$entity][]=array('name'=>'<'.$ext.'> '.$val['name'], 'destination'=>$this->context.','.$ext.',1', "extension"=>$val['extension']);
                } else {
                    $ext = $val['id'];
                    $ret[$entity][]=array('name'=>'<'.$ext.'> '.$val['name'], 'destination'=>$this->context.','.$ext.',1');
                }
            }
        }
        return $ret;
    }

    /**
     * Returns all existing extension numbers  used by the entity/class
     */
    public function getExtensions($f3) {
        $ret = array();
        $callingClass = $this->getCallingClass();
        if($this->extension_field=='') {
            return $ret;
        }
        $this->get_all=1;
        $res = $this->get($f3,1);
        $this->get_all=0;
        foreach($res as $key=>$val) {
            $ret[] = $val['extension'];
        }
        return $ret;
    }

    /**
     * Fills fields with default values if they were not provided in input request
     */
    public function setDefaults($f3,$input) {
        foreach($this->defaults as $key=>$val) {
            if(!isset($input[$key])) {
                $input[$key]=$this->defaults[$key];
            }
        }
        return $input;
    }

    /**
     * Transform input values before they are submitted to DB,
     * like imploding an array into a comma separated list.
     * The transform array should be set with callbacks to functions to preform
     * the transformation.
     */
    public function transformValues($f3,$input) {
        // Transform passed values if there are any transform callbacks defined
        // like imploding an array into a comma separated list

        foreach($input as $key=>$val) {
            if(preg_match("/([^\.]*)\.(\d+)/",$key,$matches)) {
                $parts = preg_split("/\./",$key);
                array_pop($parts);
                array_pop($parts);
                $root = implode(".",$parts);
                // is a flattened array, need to convert to one input value with array instead of individual field.x values for transformations
                unset($input[$key]);
                if($root<>'') {
                    $arrkey = $root.".".$matches[1];
                } else {
                    $arrkey = $matches[1];
                }
                if(!isset($input[$arrkey])) { $input[$arrkey]=array(); }
                array_push($input[$arrkey],$val);
            }
        }

        foreach($this->transforms as $key=>$val) {
            if(method_exists($this,$val) && isset($input[$key])) {
                $input[$key]=$this->$val($input[$key]);
            }
        }
        return $input;
    }

    /**
     * Transform values retrieved from DB to present them modified,
     * like exploding a comma separated string into an array.
     */
    public function presentationTransformValues($f3,$input) {
        foreach($this->presentationTransforms as $key=>$val) {
            if(method_exists($this,$val) && isset($input[$key])) {
                $input[$key]=$this->$val($input[$key]);
            }
        }
        return $input;
    }

    /**
     * Validates passed values. Ends with error in case of failure
     * Validation callback can modfiy/transform the returned value
     * or perform type casting
     */
    public function validateValues($f3,$input) {
        $errors=array();
        foreach($this->validations as $key=>$val) {
            if(isset($input[$key])) {
                if(is_array($val)) {
                    if(!in_array($input[$key],$val)) {
                        $errors[]=array('status'=>'422','source'=>$key,'detail'=>'Not in list of allowed values');
                    }
                } else {
                    if(method_exists($this,$val)) {
                        $input[$key]=$this->$val($input[$key],$key,$errors);
                    }
                }
            }
        }
        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }
        return $input;
    }

    /**
     * Gets post data either via headers or in request body in case
     * of JSON format
     */
    public function parseInputData($f3) {
        $errors=array();
        if($f3->get('SERVER.CONTENT_TYPE')=='application/json') {
            $input = json_decode($f3->get('BODY'),true);
            if(json_last_error() !== JSON_ERROR_NONE) {
                $error = json_last_error();
                $errors[]=array('status'=>'400','detail'=>'Could not decode JSON','code'=>$error);
                $this->dieWithErrors($errors);
            }
        } else {
            parse_str($f3->get('BODY'),$input);
        }
        return $input;
    }

    /**
     * If reload is set to true, run the apply changs method for IssabelPBX
     * to regenerate the dialplan and config files
     */
    public function applyChanges($input) {
        // run privileged apply changes
        $reload=1;
        if(isset($input['reload'])) {
            if($input['reload']!=1 && $input['reload']!='true') {
                $reload=0;
            }
        }
        if($reload==1) {
            // do reload!
            if(is_file("/usr/share/issabel/privileged/applychanges")) {
                $sComando = '/usr/bin/issabel-helper applychanges';
                $output = $ret = NULL;
                exec($sComando, $output, $ret);
            }
        }
    }

    /**
     * Flatten multi dimensional associate arraya by concatenating keys
     */
    protected function flatten($array, $prefix = '') {

        $result = array();

        if(!is_array($array)) { return $result; }

        foreach($array as $key=>$value) {
            if(is_array($value)) {
                if(count($value)==0) {
                    $result[$prefix . $key] = array();
                } else {
                    $result = $result + $this->flatten($value, $prefix . $key . '.');
                }
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    /**
     * Unflatten dotted variables into array
     */
    protected function unflatten( $collection ) {
        $collection = (array) $collection;

        $output = array();

        foreach ( $collection as $key => $value ) {
            $this->arraySet( $output, $key, $value );
            if ( is_array( $value ) && ! strpos( $key, '.' ) ) {
                $nested = $this->unflatten( $value );
                $output[$key] = $nested;
            }
        }

        return $output;
    }

    /**
     * Helper function used by unflatten
     */
    protected function arraySet( &$array, $key, $value ) {

        if(is_null($key)) { return $array = $value; }
        $keys = explode('.',$key);

        while(count($keys)>1) {
            $key = array_shift( $keys );
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }
            $array =& $array[$key];
        }

        $array[array_shift( $keys )] = $value;
        return $array;
    }

    /**
     * Returns the calling class name
     */
    protected function getCallingClass() {

       $trace = debug_backtrace();

        // Get the class that is asking for who awoke it
        $class = $trace[1]['class'];

        // +1 to i cos we have to account for calling this function
        for ( $i=1; $i<count( $trace ); $i++ ) {
            if ( isset( $trace[$i] ) ) {
                 if ( $class != $trace[$i]['class'] ) {
                     return $trace[$i]['class'];
                 }
            }
        }
    }

    protected function outputSuccess($data) {
        header('Content-Type: application/json;charset=utf-8');
        $final=array();
        $final['status']  = 'success';
        $final['results'] = $data;
        array_walk_recursive($final,function(&$item){$item=strval($item);});
        echo json_encode($final);
        die();
    }

    protected function dieWithErrors($errors) {

        $allstatuses = array();
        $final       = array();
        $status      = 'fail';

        foreach($errors as $idx=>$data) {
            $allstatuses[$data['status']]=1;
            if($data['status']=='500') { $status='error'; }
        }

        if(count($allstatuses)>1) {
            $error_status='400';
        } else {
            // only one error code, die with actual error code
            $error_status=key($allstatuses);
        }

        header('Content-Type: application/json;charset=utf-8');
        header($_SERVER['SERVER_PROTOCOL'] . ' '.$this->http_errors[$error_status], true, $error_status);
        $final['status'] = $status;
        $final['errors'] = $errors;
        echo json_encode($final);
        die();
    }

    protected function checkRequiredFields($input) {

        $errors = array();

        if(!isset($this->required_fields)) {
            return;
        }

        $checkfields=array();

        if(!is_array($this->required_fields)) {
            $checkfields = preg_split("/,/",$this->required_fields);
        } else {
            $checkfields = $this->required_fields;
        }

        foreach($checkfields as $field) {
            if(!isset($input[$field])) {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Required field missing');
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }
    }

    protected function dieExtensionDuplicate($f3,$extension) {

        $db = $f3->get('DB');

        $used_extensions = $this->getExtensions($f3);
        $getextensions = array('extensions','queues','ringgroups','conferences','parkinglots','customextensions','featurecodes');
        foreach($getextensions as $elm) {
           if($elm<>get_called_class()) {
               $obj[$elm] = new $elm($f3);
               $used_extensions = array_merge($used_extensions, $obj[$elm]->getExtensions($f3));
           }
        }

        if(in_array($extension,$used_extensions)) {
            $errors = array(array('status'=>'409','detail'=>'Extension number already in use'));
            $this->dieWithErrors($errors);
        }

        return true;

    }

    protected function setGetAll($data) {
        $this->get_all=$data;
    }

}
