<?php

class rest {

    protected $data;

    protected $table = "";

    protected $id_field   = 'id';

    protected $name_field = 'name';

    protected $dest_field = 'CONCAT("from-internal",",",extension,",1")';

    protected $extension_field = '';

    protected $search_field = 'name';

    protected $condition = null;

    protected $list_fields = array();

    protected $field_map = array();

    protected $db;

    function __construct($f3) {

        $this->db  = $f3->get('DB');

        // Use always CORS header, no matter the outcome
        $f3->set('CORS.origin','*');
        //header("Access-Control-Allow-Origin: *");

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

    }

    function get($f3) {

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
                    $otherfields = $f3->split($qparams['fields']);
                    foreach ($otherfields as $extrafield) {
                        if(isset($obj->$extrafield)) {
                            $record[$extrafield] = $obj->$extrafield;

                            if(isset($this->field_map[$extrafield])) {
                                unset($record[$extrafield]);
                                $record[$this->field_map[$extrafield]]=$obj->$extrafield;
                            }
                        }
                    }
                }

                $results[]=$record;
            }

            // for security reasons we wrap results array into one object
            // https://www.owasp.org/index.php/AJAX_Security_Cheat_Sheet#Always_return_JSON_with_an_Object_on_the_outside

            $final = array();
            $final['results'] = $results;
            header('Content-Type: application/json;charset=utf-8');
            echo json_encode($final);
            die();

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
                $extenname = $this->extension_field;

                unset($final['results'][$propid]);
                unset($final['results'][$propname]);
                $final['results']['id']          = $this->data->$propid;
                $final['results']['name']        = $this->data->$propname;
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

                header('Content-Type: application/json;charset=utf-8');
                echo json_encode($final);

                #header('Content-Type: application/json;charset=utf-8');
                #echo json_encode($this->data->cast());
            }
        }
    }

    function post($f3) {

        // INSERT record

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            die();
        }

        if($f3->get('SERVER.CONTENT_TYPE')=='application/json') {
            parse_str($f3->get('BODY'),$input);
            reset($input);
            $first_key = key($input);
            $input = json_decode($first_key,true);
        } else {
            $input = $f3->get('POST');
        }

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
            // 201 CREATED
            header("Location: $loc/".$mapid, true, 201);
            die();

        } catch(\PDOException $e) {

            //echo $db->log();
            $err=$e->errorInfo;
            print_r($err);

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

        parse_str($f3->get('BODY'),$input);

        if($f3->get('SERVER.CONTENT_TYPE')=='application/json') {
            reset($input);
            $first_key = key($input);
            $input = json_decode($first_key,true);
        }

        $f3->set('INPUT',$input);

        $field_map_reverse = array_flip($this->field_map);
        foreach($input as $key=>$val) {
            if(array_key_exists($key,$field_map_reverse)) {
                unset($input[$key]);
                $input[$field_map_reverse[$key]]=$val;
            }
        }

        try {
            $this->data->copyFrom('INPUT');
            $this->data->update();
            die();
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
        }

        //$this->data->load(array('id=?',$f3->get('PARAMS.id')));
        //header("Access-Control-Allow-Origin: *");
        //header('Content-Type: application/json;charset=utf-8');
        //echo json_encode($this->data->cast());
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
                    $otherfields = $f3->split($qparams['fields']);
                    foreach ($otherfields as $extrafield) {
                        if(isset($obj->$extrafield)) {
                            $record[$extrafield] = $obj->$extrafield;
                        }
                    }
                }

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
}
