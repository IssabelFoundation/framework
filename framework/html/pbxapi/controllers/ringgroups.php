<?php

class ringgroups extends rest {

    protected $table           = "ringgroups";
    protected $id_field        = 'grpnum';
    protected $name_field      = 'description';
    protected $extension_field = 'grpnum';
    protected $dest_field      = 'CONCAT("from-internal",",",grpnum,",1")';
    protected $list_fields     = array('grplist','strategy');
    protected $initial_exten_n = '600';
    protected $alldestinations = array();
    protected $allextensions   = array();
    protected $conn;
    protected $ami;

    function __construct($f3) {

        $mgrpass    = $f3->get('MGRPASS');

        $this->ami   = new asteriskmanager();
        $this->conn  = $this->ami->connect("localhost","admin",$mgrpass);
        if(!$this->conn) {
           header($_SERVER['SERVER_PROTOCOL'] . ' 502 Service Unavailable', true, 502);
           die();
        }

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

        $rows = $this->db->exec("SELECT * FROM alldestinations");
        foreach($rows as $row) {
            $this->alldestinations[]=$row['extension'];
            if($row['type']=='extension') {
                $this->allextensions[]=$row['extension'];
            }
        }

    }

    private function create_ringgroup($f3,$post,$method='INSERT') {

        $db = $f3->get('DB');
        $EXTEN = ($method=='INSERT')?$post['extension']:$f3->get('PARAMS.id');
        $NAME  = isset($post['name'])?$post['name']:$EXTEN;

        $valid_strategies = array('ringall','ringall-prim','hunt','hunt-prim','memoryhunt','memoryhunt-prim','firstavailable','firstnotonphone');
        if(!isset($post['strategy'])) {
            $post['strategy']='ringall';
        }
        if(!in_array($post['strategy'],$valid_strategies)) {
            $post['strategy']='ringall';
        }

        $valid_ringing = array('Ring','default'); // TODO: Get List of valid music on hold classes instead of listing only default
        if(!isset($post['ringing'])) {
            $post['ringing']='Ring';
        }
        if(!in_array($post['ringing'],$valid_strategies)) {
            $post['ringing']='Ring';
        }

        if(!isset($post['ringtimer'])) {
            $post['ringtimer']='20';
        }

        if(!isset($post['changecid'])) {
            $post['changecid']='default';
        }

        if(!isset($post['fixedcid'])) {
            $post['fixedcid']='';
        }

        // check to see if group list has valid extension numbers and discars invalid ones
        $validlist=array();
        foreach($post['grplist'] as $thisexten) {
            if(in_array($thisexten,$this->allextensions)) {
                $validlist[]=$thisexten;
            }
        }
        if(count($validlist)==0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }
        $group_list = implode("-",$validlist);

        $amidb = array(
            "RINGGROUP/$EXTEN:changecid:${post['changecid']}",
            "RINGGROUP/$EXTEN:fixedcid:${post['fixedcid']}",
        );

        if($method=='INSERT') {

            $query = 'INSERT INTO ' . $this->table . ' (' . $this->id_field . ', ' . $this->name_field . ', grplist, grptime, ringing, strategy, alertinfo, remotealert_id, needsconf, toolate_id, cwignore, cfignore, cpickup, grppre, annmsg_id)  VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $db->exec($query, array( 1 => $EXTEN, 2=> $NAME, 3 => $group_list, 4 => $post['ringtimer'], 5=>$post['ringing'], 6 => $post['strategy'], 7=>'', 8=>'0', 9=>'', 10=>'', 11=>'', 12=>'', 13=>'', 14=>'', 15=>'0'));

            foreach($amidb as &$valor) {
                list ($family,$key,$value) = preg_split("/:/",$valor,3);
                $this->ami->DatabasePut($family,$key,$value);
            }

        } else {

            $query = 'UPDATE '.$this->table.' SET '.$this->name_field.'=?, grplist=?, grptime=?, ringing=?, strategy=?  WHERE '.$this->id_field.'=?';
            $db->exec($query, array(1=>$NAME, 2=>$group_list, 3=>$post['ringtimer'], 4=>$post['ringing'],5=>$post['strategy'],6=>$EXTEN));

            foreach($amidb as &$valor) {
                list ($family,$key,$value) = preg_split("/:/",$valor,3);
                $modkey = preg_replace("/\//","_",$key);
                if(isset($post[$modkey])) {
                    $value = $post[$modkey];
                    $this->ami->DatabaseDel($family,$key);
                    $this->ami->DatabasePut($family,$key,$value);
                }
            }
        }
    }

    public function get($f3) {

        $db = $f3->get('DB');
        $rows = array();

        // GET record or collection

        // Retrieve ASTDB entries for RingGroup to return in result Object
        $astdb  = array();

        $query=  "SELECT ".$this->id_field." AS extension, ".$this->name_field." AS name, strategy, grptime AS ringtimer, grplist, ringing FROM ringgroups WHERE 1=1 ";

        if($f3->get('PARAMS.id')=='') {
            // collection

            // Get ASTDB entries
            $res = $this->ami->DatabaseShow('RINGGROUP');
            foreach($res as $key=>$val) {
                $partes = preg_split("/\//",$key);
                $astdb[$partes[3]][$partes[2]]=$val;
            }

            // Get SQL data
            $rows = $db->exec($query);

        } else {
            // individual record

            $id    = $f3->get('PARAMS.id');

            // Get ASTDB entries
            $astdb['changecid'][$id] = $this->ami->DatabaseGet("RINGGROUP/$id",'changecid');
            $astdb['fixedcid'][$id]  = $this->ami->DatabaseGet("RINGGROUP/$id",'fixedcid');

            // Get SQL data
            $query.= "AND grpnum=:id";
            $rows = $db->exec($query,array(':id'=>$id));
        }

        foreach($rows as $idx=>$row) {
            $ring_extensions = preg_split("/-/",$row['grplist']);
            $rows[$idx]['grplist']=$ring_extensions;
            $rows[$idx]['changecid']=$astdb['changecid'][$row['extension']];
            $rows[$idx]['fixedcid']=$astdb['fixedcid'][$row['extension']];
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

        // Expect JSON data, if its not good, fail
        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        // Put *requires* and id resource to be SET, as it will be used to update or insert with specified id
        if($f3->get('PARAMS.id')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            die();
        }

        // Required post fields
        if(!isset($input['grplist'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        $EXTEN = $f3->get('PARAMS.id');
        $this->data->load(array($this->id_field.'=?',$EXTEN));

        if ($this->data->dry()) {

            // No entry with that extension/id, this is an INSERT, extension number is the one in the URL

            $this->checkValidExtension($f3,$f3->get('PARAMS.id'));

            $input['extension'] = $f3->get('PARAMS.id');
            $this->create_ringgroup   ($f3, $input, 'INSERT');

            $this->applyChanges($input);

            // Return new entity in Location header
            $loc    = $f3->get('REALM');
            header("Location: $loc/".$EXTEN, true, 201);
            die();

        } else {

            // Exising user with specified extension/id, this is aun UPDATE

            // Populate variable with existing values from entry in users table
            // and override stored values with passed ones
            $this->data->copyTo('currentvalues');

            foreach($f3->get('currentvalues') as $key=>$val) {
                 $input[$key] = isset($input[$key])?$input[$key]:$f3->get('currentvalues')[$key];
            }

            $input['extension'] = $f3->get('PARAMS.id');
            $this->create_ringgroup   ($f3, $input, 'UPDATE');

            $this->applyChanges($input);
        }

    }

    public function post($f3) {

        // Users table is the one to track extensions, if there is a user entry asume extension is already created
        // in related tables devices and sip

        $db = $f3->get('DB');

        // Expect JSON data, if its not good, fail
        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        // If post has an ID, fail, it will create a new resource with next available id
        if($f3->get('PARAMS.id')!='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            die();
        }

        // Required post fields
        if(!isset($input['grplist'])) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        // Get next extension number from the users table, including gap extensions
        $idfield = $this->id_field;
        $query = "SELECT cast($idfield AS unsigned)+1 AS extension FROM ringgroups mo WHERE NOT EXISTS ";
        $query.= "(SELECT NULL FROM ringgroups mi WHERE cast(mi.$idfield AS unsigned) = CAST(mo.$idfield AS unsigned)+ 1) ";
        $query.= "ORDER BY CAST($idfield AS unsigned) LIMIT 1";
        $rows  = $db->exec($query);
        $EXTEN = $rows[0]['extension'];
        if($EXTEN=='') { $EXTEN=$this->initial_exten_n; }
        $input['extension'] = $EXTEN;

        // Check if extension number is valid and it has no collitions
        $this->checkValidExtension($f3,$EXTEN);

        // Create proper entries in DB and ASTDB
        $this->create_ringgroup   ($f3, $input, 'INSERT');

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc    = $f3->get('REALM');
        header("Location: $loc/".$EXTEN, true, 201);
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

    private function applyChanges($input) {
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

    private function checkValidExtension($f3,$extension) {

        $db = $f3->get('DB');

        if(in_array($extension,$this->alldestinations)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict', true, 409);
            die();
        }

        /*
        // Check ringgroup limit restriction, fail if reached
        $rows = $db->exec("SELECT count(*) AS cuantos FROM users HAVING cuantos<?",array(1=>$this->extension_limit));

        if(count($rows)==0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 507 Insufficient Storage', true, 507);
            die();
        }

        */

        // TODO: check valid extension range and no collision with other destinations
        return true;

    }

}


