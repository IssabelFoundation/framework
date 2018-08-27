<?php

class rest {

    protected $data;

    protected $table = "";

    protected $id_field   = 'id';

    protected $name_field = 'name';

    protected $dest_field = 'CONCAT("from-internal",",",extension,",1")';

    protected $extension_field = 'extension';

    protected $search_field = 'name';

    protected $condition = null;

    protected $list_fields = array();

    protected $field_map = array();

    protected $initial_exten_n = '200';

    protected $defaults = array();

    protected $transforms = array();
 
    protected $presentation_transforms = array();

    protected $validations = array();

    protected $db;

    function __construct($f3) {

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

        if($this->extension_field<>'') {
            $this->field_map[$this->extension_field]='extension';
        }

        if($this->name_field<>'') {
            $this->field_map[$this->name_field]='name';
        }

    }

    function get($f3, $from_child) {

        // GET record or collection

        if($f3->get('PARAMS.id')=='') {

            // whole collection
            // we would never have a too big of collection, so store in memory for simplicity?

            $list = $this->data->find($this->condition);

            $results = array();

            foreach ($list as $obj) {

                $record    = array();
                $propid    = $this->id_field;
                $propname  = $this->name_field;
                $extenname = $this->extension_field;

                $record['id']          = $obj->$propid;
                $record['name']        = $obj->$propname;
                if($extenname<>'') {
                    $record['extension']   = $obj->$extenname;
                }
                if($this->dest_field<>'') {
                    $record['destination'] = $obj->destination;
                }

                foreach ($this->list_fields as $extrafield) {
                    $record[$extrafield] = $obj->$extrafield;

                    if(isset($this->field_map[$extrafield])) {
                        unset($record[$extrafield]);
                        $record[$this->field_map[$extrafield]]=$obj->$extrafield;
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

                $record = $this->presentation_transform_values($f3,$record);

                $results[]=$record;

            }

            // for security reasons we wrap results array into one object
            // https://www.owasp.org/index.php/AJAX_Security_Cheat_Sheet#Always_return_JSON_with_an_Object_on_the_outside

            if(is_array($from_child)) {
                $final = array();
                $final['results'] = $results;
                header('Content-Type: application/json;charset=utf-8');
                echo json_encode($final);
                die();
            } else {
               // return data to child class
               return $results;
            }

        } else {

            // individual record

            $this->data->load(array($this->id_field.'=?',$f3->get('PARAMS.id')));

            if ($this->data->dry()) {

                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
                die();

            } else {

                $final = array();
                $final['results'] = $this->data->cast();

                $propid    = $this->id_field;
                $propname  = $this->name_field;
                $propexten = $this->extension_field;

                unset($final['results'][$propid]);
                unset($final['results'][$propname]);
                $final['results']['id']          = $this->data->$propid;
                $final['results']['name']        = $this->data->$propname;
                if($propexten<>'') {
                    $final['results']['etension']    = $this->data->$propexten;
                }

                if($this->dest_field<>'') {
                    $final['results']['destination'] = $this->data->destination;
                    unset($final['results'][$this->dest_field]);
                }

                foreach($final['results'] as $key=>$val) {
                    if(isset($this->field_map[$key])) {
                        unset($final['results'][$key]);
                        $final['results'][$this->field_map[$key]]=$val;
                    }
                }

                $final['results'] = $this->presentation_transform_values($f3,$final['results']);

                if($from_child==0) { 
                    header('Content-Type: application/json;charset=utf-8');
                    echo json_encode($final);
                } else {
                    return $final;
                }

            }
        }
    }

    function post($f3,$from_child) {

        // INSERT record

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            die();
        }

        $input = $this->parse_input_data($f3);

        // Get next extension number from proper table, filling gaps if any
        $extenfield = $this->extension_field;
        $query = "SELECT cast($extenfield AS unsigned)+1 AS extension FROM ".$this->table." mo WHERE NOT EXISTS ";
        $query.= "(SELECT NULL FROM ".$this->table." mi WHERE cast(mi.$extenfield AS unsigned) = CAST(mo.$extenfield AS unsigned)+ 1) ";
        $query.= "ORDER BY CAST($extenfield AS unsigned) LIMIT 1";
        $rows  = $this->db->exec($query);
        $EXTEN = $rows[0]['extension'];
        if($EXTEN=='') { $EXTEN=$this->initial_exten_n; }
        $input['extension'] = $EXTEN;

        // Transform values passed if needed
        $input = $this->transform_values($f3,$input);
        $input = $this->validate_values($f3,$input);

        // Set default values if not passed via request, defaults uses the mapped/human readable field name
        $input = $this->fill_with_defaults($f3,$input);

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
                // 201 CREATED
                header("Location: $loc/$mapid", true, 201);
                die();
            } else {
                return $mapid;
            }

        } catch(\PDOException $e) {

            //echo $db->log();
            $err=$e->errorInfo;

            if ($e->getCode() != 23000) {
                // when trying to insert duplicate
                header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict', true, 409);
            } else {
                // on other errors
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            }
            die();
        }
    }

    function put($f3) {

        // UPDATE Record

        if($f3->get('PARAMS.id')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        $this->data->load(array($this->id_field.'=?',$f3->get('PARAMS.id')));

        if ($this->data->dry()) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            die();
        }

        $input = $this->parse_input_data($f3);

        $input = $this->transform_values($f3,$input);
        $input = $this->validate_values($f3,$input);

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
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        }

    }

    function delete($f3) {

        // DELETE record
        if($f3->get('PARAMS.id')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            $this->data->load(array($this->id_field.'=?',$oneid));

            if ($this->data->dry()) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
                die();
            }

            try {
                $this->data->erase();
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

        }

    }

    public function search($f3) {

        if($f3->get('PARAMS.term')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

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
            $record['destination'] = $obj->destination;

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

            $record = $this->presentation_transform_values($f3,$record);

            $results[]=$record;
        }

        // for security reasons we wrap results array into one object
        // https://www.owasp.org/index.php/AJAX_Security_Cheat_Sheet#Always_return_JSON_with_an_Object_on_the_outside

        $final = array();
        $final['results'] = $results;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();

    }

    public function fill_with_defaults($f3,$input) {
        foreach($this->defaults as $key=>$val) {
            if(!isset($input[$key])) {
                $input[$key]=$this->defaults[$key];
            }
        }
        return $input;
    }

    public function transform_values($f3,$input) {
        // Transform passed values if there are any transform callbacks defined
        // like imploding an array into a comma separated list
        foreach($this->transforms as $key=>$val) {
            if(method_exists($this,$val) && isset($input[$key])) {
                $input[$key]=$this->$val($input[$key]);
            }
        }
        return $input;
    }

    public function presentation_transform_values($f3,$input) {
        // Transform passed values if there are any transform callbacks defined
        // like imploding an array into a comma separated list
        foreach($this->presentation_transforms as $key=>$val) {
            if(method_exists($this,$val) && isset($input[$key])) {
                $input[$key]=$this->$val($input[$key]);
            }
        }
        return $input;
    }

    public function validate_values($f3,$input) {
        // Validates passed values and error out or return valid option
        foreach($this->validations as $key=>$val) {
            if(isset($input[$key])) {
                if(is_array($val)) {
                    if(!in_array($input[$key],$val)) {
                        $input[$key]=$val[0];
                    }
                } else {
                    if(method_exists($this,$val)) {
                        $input[$key]=$this->$val($input[$key]);
                    }
                }
            }
        }
        return $input;
    }

    public function parse_input_data($f3) {
        // gets post data either in headers or in body in case of json
        if($f3->get('SERVER.CONTENT_TYPE')=='application/json') {
            $input = json_decode($f3->get('BODY'),true);
            if(json_last_error() !== JSON_ERROR_NONE) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                die();
            }
        } else {
            parse_str($f3->get('BODY'),$input);
        }
        return $input;
    }

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

}
