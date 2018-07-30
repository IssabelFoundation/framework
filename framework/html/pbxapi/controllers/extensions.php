<?php

class extensions extends rest {
    protected $table           = "users";
    protected $id_field        = 'extension';
    protected $name_field      = 'name';
    protected $extension_field = 'extension';
    protected $dest_field      = 'CONCAT("from-internal",",",extension,",1")';
    protected $conn;
    protected $ami;
    protected $extension_limit = 500;

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

        if(is_readable('/var/www/db/extlimit')) {
            $this->extension_limit = intval(file_get_contents("/var/www/db/extlimit"));
        }

    }

    public function get($f3) {

        $db = $f3->get('DB');
        $rows = array();

        // GET record or collection

        $query=  "SELECT a.extension,b.description AS name,b.tech,b.dial,c.data AS secret FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN sip c ON a.extension=c.id WHERE c.keyword='secret' ";

        if($f3->get('PARAMS.id')=='') {
            // collection
            $rows = $db->exec($query);
        } else {
            // individual record
            $query.= "AND a.extension=:id";
            $id    = $f3->get('PARAMS.id');
            $rows = $db->exec($query,array(':id'=>$id));
        }

         // final json output
         $final = array();
         $final['results'] = $rows;
         header('Content-Type: application/json;charset=utf-8');
         echo json_encode($final);
         die();
    }

    private function create_user($f3,$post,$method='INSERT') {

        $db = $f3->get('DB');

        $EXTEN = ($method=='INSERT')?$post['extension']:$f3->get('PARAMS.id');
        $NAME  = isset($post['name'])?$post['name']:$EXTEN;

        if(!isset($post['cidname'])) {
            $post['cidname']=$NAME;
        }

        if(!isset($post['voicemail'])) {
            $VOICEMAIL='novm';
        } else {
            $VOICEMAIL=$post['voicemail'];
            if($VOICEMAIL<>'novm' || $VOICEMAIL<>'default') {
                $VOICEMAIL='novm';
            }
        }

        /*
        if($VOICEMAIL<>'novm') {
            exec("rm -f /var/spool/asterisk/voicemail/device/".$EXTEN);
            exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
        }
        */


        if(!isset($post['ringtimer'])) {
            $RINGTIMER=0;
        } else {
            $RINGTIMER=intval($post['ringtimer']);
        }

        $amidb = array(
            "AMPUSER/$EXTEN:answermode:disabled",
            "AMPUSER/$EXTEN:cfringtimer:0",
            "AMPUSER/$EXTEN:cidname:$NAME",
            "AMPUSER/$EXTEN:cidnum:$EXTEN",
            "AMPUSER/$EXTEN:concurrency_limit:0",
            "AMPUSER/$EXTEN:device:$EXTEN",
            "AMPUSER/$EXTEN:language:''",
            "AMPUSER/$EXTEN:noanswer:''",
            "AMPUSER/$EXTEN:outboundcid:''",
            "AMPUSER/$EXTEN:password:''",
            "AMPUSER/$EXTEN:queues/qnostate:usestate",
            "AMPUSER/$EXTEN:recording:''",
            "AMPUSER/$EXTEN:recording/in/external:dontcare",
            "AMPUSER/$EXTEN:recording/in/internal:dontcare",
            "AMPUSER/$EXTEN:recording/ondemand:disabled",
            "AMPUSER/$EXTEN:recording/out/external:dontcare",
            "AMPUSER/$EXTEN:recording/out/internal:dontcare",
            "AMPUSER/$EXTEN:recording/priority:0",
            "AMPUSER/$EXTEN:ringtimer:$RINGTIMER",
            "AMPUSER/$EXTEN:voicemail:novm",
            "CW:$EXTEN:ENABLED",
            "CALLTRACE:$EXTEN:$EXTEN"
        );

        if($method=='INSERT') {

            $db->exec('INSERT INTO users(extension, name, voicemail, ringtimer) VALUES(?,?,?,?)',
                array(1=>$EXTEN,2=>$NAME,3=>$VOICEMAIL,4=>$RINGTIMER));

            foreach($amidb as &$valor) {
                list ($family,$key,$value) = preg_split("/:/",$valor,3);
                $this->ami->DatabasePut($family,$key,$value);
            }

        } else {
            $db->exec('UPDATE users SET name=?, voicemail=?, ringtimer=? WHERE extension=?',
                array(1=>$NAME, 2=>$VOICEMAIL, 3=>$RINGTIMER, 4=>$EXTEN));

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

    private function create_sip($f3, $input, $method='INSERT') {

        $db = $f3->get('DB');
        $id = $f3->get('PARAMS.id');

        $EXTEN  = $input['extension'];
        $NAME   = $input['name'];
        $SECRET = isset($input['secret'])?$input['secret']:$this->generateRandomString(32);

        $sipset = array(
            array("secret",      "$SECRET",          2),
            array("dtmfmode",    "rfc2833",          3),
            array("canreinvite", "no",               4),
            array("context",     "from-internal",    5),
            array("host",        "dynamic",          6),
            array("trustrpid",   "yes",              7),
            array("sendrpid",    "no",               8),
            array("type",        "friend",           9),
            array("nat",         "yes",             10),
            array("port",        "5060",            11),
            array("qualify",     "yes",             12),
            array("qualifyfreq", "60",              13),
            array("transport",   "udp",             14),
            array("avpf",        "no",              15),
            array("icesupport",  "no",              16),
            array("dtlsenable",  "no",              17),
            array("dtlsverify",  "no",              18),
            array("dtlssetup",   "actpass",         19),
            array("encryption",  "no",              20),
            array("callgroup",   "",                21),
            array("pickupgroup", "",                22),
            array("disallow",    "",                23),
            array("allow",       "",                24),
            array("dial",        "SIP/$EXTEN",      25),
            array("accountcode", "",                26),
            array("mailbox",     "$EXTEN@device",   27),
            array("deny",        "0.0.0.0/0.0.0.0", 28),
            array("permit",      "0.0.0.0/0.0.0.0", 29),
            array("account",     "$EXTEN",          30),
            array("callerid",    "device <$EXTEN>", 31)
        );

        if($method=='INSERT') {
            $db->exec('INSERT INTO devices (id,tech,dial,devicetype,user,description,emergency_cid) VALUES (?,?,?,?,?,?,?)',
                array(1=>$EXTEN, 2=>'sip', 3=>"SIP/$EXTEN", 4=>'fixed', 5=>$EXTEN, 6=>$NAME, 7=>''));
        } else {
            $db->exec('UPDATE devices SET tech=?,dial=?,devicetype=?,user=?,description=?,emergency_cid=? WHERE id=?',
                array(1=>'sip', 2=>"SIP/$EXTEN", 3=>'fixed', 4=>$EXTEN, 5=>$NAME, 6=>'', 7=>$EXTEN ));
        }

        foreach($sipset as &$valor) {

            if(isset($input[$valor[0]])) {
                // we have a input with same field name, use it instead of hardcoded value for sip table
                $valor[1]=$input[$valor[0]];
            }

            if($method=='INSERT') {
                $db->exec("INSERT INTO sip (id,keyword,data,flags) VALUES (?,?,?,?)",
                    array(1=>$EXTEN,2=>$valor[0],3=>$valor[1],4=>$valor[2]));
            } else {
                $db->exec("UPDATE sip SET data=?,flags=? WHERE id=? AND keyword=?",
                    array(1=>$valor[1],2=>$valor[2],3=>$EXTEN,4=>$valor[0]));
            }
        }

        if($method=='INSERT') {
            // Only
            $amidb =  array(
                "DEVICE/$EXTEN:default_user:$EXTEN",
                "DEVICE/$EXTEN:dial:SIP/$EXTEN",
                "DEVICE/$EXTEN:type:fixed",
                "DEVICE/$EXTEN:user:$EXTEN"
            );

            foreach($amidb as &$valor) {
                list ($family,$key,$value) = preg_split("/:/",$valor);
                $this->ami->DatabasePut($family,$key,$value);
            }
        }
    }

    public function put($f3) {

        $db    = $f3->get('DB');

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

        // We use the users table to track, using the Database Mapper
        $EXTEN = $f3->get('PARAMS.id');
        $this->data->load(array($this->id_field.'=?',$EXTEN));

        if ($this->data->dry()) {

            // No user with that extension/id, this is an INSERT, extension number is the one in the URL

            $this->checkValidExtension($f3,$f3->get('PARAMS.id'));

            $input['extension'] = $f3->get('PARAMS.id');
            $this->create_sip   ($f3, $input, 'INSERT');
            $this->create_user  ($f3, $input, 'INSERT');

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
            $this->create_sip   ($f3, $input, 'UPDATE');
            $this->create_user  ($f3, $input, 'UPDATE');

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

        /*
        $required_variables = array('name');
        $error=0;
        foreach($post as $key=>$val) {
            if(!in_array($key,$required_variables)) {
                $error++;
            }
        }

        if($error>0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessabel Entity', true, 422);
            die();
        }
        */

        // Get next extension number from the users table, including gap extensions
        $query = "SELECT cast(extension AS unsigned)+1 AS extension FROM users mo WHERE NOT EXISTS ";
        $query.= "(SELECT NULL FROM users mi  WHERE  cast(mi.extension AS unsigned) = CAST(mo.extension AS unsigned)+ 1) ";
        $query.= "ORDER BY CAST(extension AS unsigned) LIMIT 1";
        $rows  = $db->exec($query);
        $EXTEN = $rows[0]['extension'];
        $input['extension'] = $EXTEN;

        // Check if extension number is valid and it has no collitions
        $this->checkValidExtension($f3,$EXTEN);

        // Default name if not supplied in data
        if(!isset($input['name'])) {
            $input['name'] = 'Extension';
        }

        // Remove any stray DB entries in devices and sip tables
        $db->exec("DELETE FROM devices WHERE id = ?", array(1=>$EXTEN));
        $db->exec("DELETE FROM sip WHERE id = ?",     array(1=>$EXTEN));

        // Create proper entries in DB and ASTDB
        $this->create_sip   ($f3, $input, 'INSERT');
        $this->create_user  ($f3, $input, 'INSERT');

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

            // Delete from sip table using SQL
            try {
                $db->exec("DELETE FROM sip WHERE id=?",array(1=>$oneid));
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

            // Delete from devices table using SQL
            try {
                $db->exec("DELETE FROM devices WHERE id=?",array(1=>$oneid));
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

            // Delete all relevant ASTDB entries
            $this->ami->DatabaseDelTree('AMPUSER/'.$oneid);
            $this->ami->DatabaseDelTree('CALLTRACE/'.$oneid);
            $this->ami->DatabaseDelTree('CW/'.$oneid);
            $this->ami->DatabaseDelTree('DEVICE/'.$oneid);
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

    private function generateRandomString($length = 10) {
        // Used for generating password if not supplied when inserting extensions

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function checkValidExtension($f3,$extension) {

        $db = $f3->get('DB');

        // Check extension limit restriction, fail if reached
        $rows = $db->exec("SELECT count(*) AS cuantos FROM users HAVING cuantos<?",array(1=>$this->extension_limit));

        if(count($rows)==0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 507 Insufficient Storage', true, 507);
            die();
        }

        // TODO: check valid extension range and no collision with other destinations
        return true;

    }

}


