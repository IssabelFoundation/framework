<?php

class queues extends rest {

    protected $table           = "queues_config";
    protected $id_field        = 'extension';
    protected $name_field      = 'descr';
    protected $extension_field = 'extension';
    protected $dest_field      = 'CONCAT("ext-queues",",",extension,",1")';
    protected $list_fields     = array('timeout','strategy');
    protected $initial_exten_n = '2000';
    protected $alldestinations = array();
    protected $allextensions   = array();
    protected $staticmembers   = array();
    protected $conn;
    protected $ami;

    // fields from queues_config table
    protected $config_fields = array( 
        'alertinfo',
        'ringing',
        'maxwait',
        'password',
        'ivr_id',
        'dest',
        'cwignore',
        'qregex',
        'agentannounce_id',
        'joinannounce_id',
        'queuewait',
        'use_queue_context',
        'togglehint',
        'qnoanswer',
        'callconfirm',
        'callconfirm_id',
        'monitor_type',
        'monitor_heard',
        'monitor_spoken',
        'callback_id',
        'destcontinue',
        'grppre',
        'descr'
    );

    // fields from queues_details table
    protected $details_fields = array(
        'announce-frequency',
        'announce-holdtime',
        'announce-position',
        'answered_elsewhere',
        'autofill',
        'autopause',
        'autopausebusy',
        'autopausedelay',
        'autopauseunavail',
        'cron_schedule',
        'eventmemberstatus',
        'eventwhencalled',
        'joinempty',
        'leavewhenempty',
        'maxlen',
        'memberdelay',
        'monitor-format',
        'monitor-join',
        'penaltymemberslimit',
        'periodic-announce-frequency',
        'queue-callswaiting',
        'queue-thankyou',
        'queue-thereare',
        'queue-youarenext',
        'reportholdtime',
        'retry',
        'ringinuse',
        'servicelevel',
        'skip_joinannounce',
        'strategy',
        'timeout',
        'timeoutpriority',
        'timeoutrestart',
        'weight',
        'wrapuptime',
        'music'
    );

    protected $default = array(
        'ringing'                      =>  '0',
        'ivr_id'                       =>  'none',
        'cwignore'                     =>  '0',
        'agentannounce_id'             =>  '0',
        'joinannounce_id'              =>  '0',
        'queuewait'                    =>  '0',
        'use_queue_context'            =>  '0',
        'togglehint'                   =>  '0',
        'qnoanswer'                    =>  '0',
        'callconfirm'                  =>  '0',
        'callconfirm_id'               =>  '0',
        'monitor_heard'                =>  '0',
        'monitor_spoken'               =>  '0',
        'callback_id'                  =>  'none',
        'timeoutpriority'              =>  'app',
        'autopauseunavail'             =>  'no',
        'autopausebusy'                =>  'no',
        'cron_schedule'                =>  'never',
        'skip_joinannounce'            =>  '',
        'timeoutrestart'               =>  'no',
        'memberdelay'                  =>  '0',
        'servicelevel'                 =>  '60',
        'autopausedelay'               =>  '0',
        'autopause'                    =>  'no',
        'reportholdtime'               =>  'no',
        'ringinuse'                    =>  'yes',
        'autofill'                     =>  'no',
        'weight'                       =>  '0',
        'eventmemberstatus'            =>  'no',
        'eventwhencalled'              =>  'no',
        'monitor-join'                 =>  'yes',
        'monitor-format'               =>  '',
        'periodic-announce-frequency'  =>  '0',
        'queue-thankyou'               =>  '',
        'queue-callswaiting'           =>  'silence/1',
        'queue-youarenext'             =>  'silence/1',
        'queue-thereare'               =>  'silence/1',
        'announce-position'            =>  'no',
        'announce-holdtime'            =>  'no',
        'announce-frequency'           =>  '0',
        'wrapuptime'                   =>  '0',
        'retry'                        =>  '5',
        'timeout'                      =>  '15',
        'strategy'                     =>  'ringall',
        'leavewhenempty'               =>  'no',
        'joinempty'                    =>  'yes',
        'maxlen'                       =>  '0',
        'answered_elsewhere'           =>  '0',
        'penaltymemberslimit'          =>  '0',
    );


    protected $field_map = array(
        'agentannounce_id'             => 'agent_announce_id',
        'alertinfo'                    => 'alert_info',
        'announce-frequency'           => 'announce_frequency',
        'announce-holdtime'            => 'announce_holdtime',
        'announce-position'            => 'announce_position',
        'answered_elsewhere'           => 'answered_elsewhere',
        'ringing'                      => 'music_on_hold_ringing',
        'autofill'                     => 'autofill',
        'autopause'                    => 'auto_pause',
        'autopausebusy'                => 'auto_pause_if_busy',
        'autopausedelay'               => 'auto_pause_delay',
        'autopauseunavail'             => 'auto_pause_if_unavailable',
        'callback_id'                  => 'callback_id',
        'callconfirm'                  => 'call_confirm',
        'music'                        => 'music_on_hold_class',
        'callconfirm_id'               => 'call_confirm_announce_id',
        'cron_schedule'                => 'cron_schedule',
        'cwignore'                     => 'skip_busy_agents',
        'dest'                         => 'destination',
        'destcontinue'                 => 'destination_on_continue',
        'eventmemberstatus'            => 'event_member_status',
        'eventwhencalled'              => 'event_when_called',
        'ivr_id'                       => 'breakout_ivr_id',
        'joinannounce_id'              => 'join_announce_id',
        'joinempty'                    => 'join_empty',
        'leavewhenempty'               => 'leave_when_empty',
        'use_queue_context'            => 'agent_restrictions',
        'maxlen'                       => 'max_callers_waiting',
        'strategy'                     => 'ring_strategy',
        'maxwait'                      => 'max_wait',
        'memberdelay'                  => 'member_delay',
        'monitor-format'               => 'monitor_format',
        'monitor-join'                 => 'monitor_join',
        'monitor_heard'                => 'monitor_heard',
        'monitor_spoken'               => 'monitor_spoken',
        'monitor_type'                 => 'monitor_type',
        'penaltymemberslimit'          => 'penalty_members_limit',
        'periodic-announce-frequency'  => 'periodic_announce_frequency',
        'qnoanswer'                    => 'queue_no_answer',
        'qregex'                       => 'queue_regular_expression',
        'queue-callswaiting'           => 'sound_calls_waiting',
        'queue-thankyou'               => 'sound_thank_you',
        'queue-thereare'               => 'sound_there_are',
        'queue-youarenext'             => 'sound_you_are_next',
        'queuewait'                    => 'wait_time_prefix',
        'grppre'                       => 'cid_name_prefix',
        'reportholdtime'               => 'report_hold_time',
        'retry'                        => 'agent_retry',
        'ringinuse'                    => 'ring_busy_members',
        'servicelevel'                 => 'service_level',
        'skip_joinannounce'            => 'skip_join_announce',
        'timeoutpriority'              => 'timeout_priority',
        'timeoutrestart'               => 'timeout_restart',
        'togglehint'                   => 'generate_device_hints',
        'wrapuptime'                   => 'wrapup_time',
        'extension'                    => 'extension',
        'descr'                        => 'name',
        'password'                     => 'password',
        'timeout'                      => 'timeout',
        'weight'                       => 'weight'
    );

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

        // Static members 
        $rows = $this->db->exec("SELECT id AS exten,GROUP_CONCAT(data separator '^') as member FROM queues_details WHERE keyword='member' GROUP BY id");
        foreach($rows as $row) {
            $this->staticmembers[$row['exten']]=$row['member'];
        }

    }

    private function create_queue($f3,$post,$method='INSERT') {

        $db = $f3->get('DB');
        $EXTEN = ($method=='INSERT')?$post['extension']:$f3->get('PARAMS.id');

        // check to see if static member list has valid extension numbers and bails out if one invalid is found 
        if(isset($post['static_members'])) {
            foreach($post['static_members'] as $thisexten) {
                list($exten,$penalty) = preg_split("/,/",$thisexten);
                $output = preg_replace( '/[^0-9]/', '', $exten );
                if(!in_array($output,$this->allextensions)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                    die();
                }
            }
        }

        $amidb=array();

        // check to see if dynamic member list has valid extension numbers and bails out if one invalid is found 
        if(isset($post['dynamic_members'])) {
            foreach($post['dynamic_members'] as $thisexten) {
                list($exten,$penalty) = preg_split("/,/",$thisexten);
                $output = preg_replace( '/[^0-9]/', '', $exten );
                if(!in_array($output,$this->allextensions)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
                    die();
                } else {
                    $amidb[] = "QPENALTY/$EXTEN/agents:$exten:$penalty";
                }
            }
        }

        $amidb[] = "QPENALTY/$EXTEN:dynmemberonly:${post['restrict_dynamic_agents']}";

        if($method=='INSERT') {

            $fldconfig     = array();
            $fldconfigval  = array();

            foreach($this->config_fields as $field) {
                $fldconfig[]     = "`$field`";
                $def             = isset($this->default[$field])?$this->default[$field]:'';
                $fldconfigval[]  = isset($post[$field])?$post[$field]:$def;
            }
            $fldconfig[]    = 'extension';
            $fldconfigval[] = $EXTEN;

            $mark=array();
            $allfields = implode(",",$fldconfig);
            for($a=0;$a<count($fldconfig);$a++) { $mark[]='?'; }
            $markvalues = implode(",",$mark);

            $query = 'INSERT INTO '.$this->table.' ( '.$allfields.' ) VALUES ('.$markvalues.')';
            try {
                $db->exec($query, $fldconfigval);
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

            foreach($this->details_fields as $field) {
                $keyword  = $field;
                $def      = isset($this->default[$field])?$this->default[$field]:'';
                $data     = isset($post[$field])?$post[$field]:$def;
                $query    = 'INSERT INTO queues_details (id,keyword,data) VALUES (?,?,?)';
                
                try {
                    $db->exec($query, array($EXTEN,$keyword,$data));
                } catch(\PDOException $e) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                    die();
                }

            }

        } else {

            $fldconfig     = array();
            $fldconfigval  = array();
            $flddetails    = array();
            $flddetailsval = array();

            foreach($post as $key=>$val) {
                if(in_array($key,$this->config_fields)) { 
                    $fldconfig[]="`$key`=?";
                    $fldconfigval[]=$val;
                } else if(in_array($key,$this->details_fields)) {
                    $flddetails[$key]=$val;
                }
            }
            $fldconfigval[]=$EXTEN;

            $allconfigfields = implode(",",$fldconfig);

            // Update queues_config table
            $query = 'UPDATE '.$this->table.' SET '.$allconfigfields.' WHERE extension=?';
            try {
                $db->exec($query, $fldconfigval);
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

            // Update queues_details table
            $db->exec("DELETE FROM queues_details WHERE id=?",array($EXTEN));

            foreach($flddetails as $keyword=>$data) {
                $query = 'INSERT INTO queues_details (id,keyword,data) VALUES (?,?,?)';
                try {
                    $db->exec($query,array($EXTEN,$keyword,$data));
                } catch(\PDOException $e) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                    die();
                }
            }
        }

        // Set static members on queues_details
        foreach($post['static_members'] as $data) {

            $data = $this->expand_static_member($data);
   
            $query = 'INSERT INTO queues_details (id,keyword,data) VALUES (?,?,?)';
            try {
                $db->exec($query,array($EXTEN,'member',$data));
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }
        }

        $this->ami->DatabaseDelTree('QPENALTY/'.$EXTEN);
        foreach($amidb as &$valor) {
            list ($family,$key,$value) = preg_split("/:/",$valor,3);
            $this->ami->DatabaseDel($family,$key);
            $this->ami->DatabasePut($family,$key,$value);
        }
    }

    public function get($f3) {

        // GET record or collection

        $db = $f3->get('DB');
        $rows = array();

        // Build SQL query with fields from both tables
        $i=0;
        $addfields=array();
        $joinfields=array();
        foreach($this->details_fields as $field) {
           $addfields[$field]="IFNULL(q$i.data,'') AS `$field`";
           $joinfields[$field]=" LEFT JOIN queues_details q$i on q$i.id=extension AND q$i.keyword='$field' ";
           $i++;
        }

        $joinedf = implode(",",$addfields);
        $joinedj = implode(" ",$joinfields);

        $tablef  = implode(",",$this->config_fields);
        $query = "SELECT ".$this->id_field." AS extension, ".$this->name_field." AS name, $tablef, $joinedf FROM queues_config $joinedj WHERE 1=1 ";

        $astdb = $this->get_astdb_qpenalty();

        if($f3->get('PARAMS.id')=='') {
            // collection
            // Get SQL data
            $rows = $db->exec($query);

        } else {
            // individual record
            $id    = $f3->get('PARAMS.id');

            // limit astdb to individual record
            $final = array();
            $final[$id] = $astdb[$id];
            $astdb = $final;
          
            // Get SQL data
            $query.= "AND extension=:id";
            $rows = $db->exec($query,array(':id'=>$id));
        }

        // Add astdb to response and reformat field results if needed
        foreach($rows as $idx=>$row) {
            $agents = isset($astdb[$row['extension']]['agents'])?$astdb[$row['extension']]['agents']:array();
            $rows[$idx]['dynamic_members']=$agents;
            $rows[$idx]['restrict_dynamic_agents']=$astdb[$row['extension']]['dynmemberonly'];

            // convert static members db config to abstract config (tech)number,penalty 
            if(isset($this->staticmembers[$row['extension']])) {
                $el = preg_split("/\^/",$this->staticmembers[$row['extension']]);
                foreach($el as $memb) {
                    $allstaticmembers[]=$this->reduce_static_member($memb);
                }
                $rows[$idx]['static_members']=$allstaticmembers;
            } else {
                $rows[$idx]['static_members']=array();
            }
            
            foreach($row as $fld=>$val) {
                if(isset($this->field_map[$fld])) {
                     unset($rows[$idx][$fld]);
                     $rows[$idx][$this->field_map[$fld]]=$val;
                } 
            }
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

        $EXTEN = $f3->get('PARAMS.id');
        $this->data->load(array($this->id_field.'=?',$EXTEN));

        if ($this->data->dry()) {

            // No entry with that extension/id, this is an INSERT, extension number is the one in the URL

            $this->checkValidExtension($f3,$f3->get('PARAMS.id'));

            $input['extension'] = $f3->get('PARAMS.id');
            $input['descr']     = isset($input['name'])?$input['name']:$input['extension']; // set default name if not specified
            unset($input['name']);

            $this->create_queue($f3, $input, 'INSERT');

            //$this->applyChanges($input);

            // Return new entity in Location header
            $loc    = $f3->get('REALM');
            header("Location: $loc/".$EXTEN, true, 201);
            die();

        } else {

            // Exising queue with specified extension/id, this is aun UPDATE

            // Populate variable with existing values from entry in queues_config table
            // and override stored values with passed ones
            $this->data->copyTo('currentvalues');

            $morefields=array();
            $rows = $db->exec("SELECT * FROM queues_details WHERE id=?",array($f3->get('PARAMS.id')));
            foreach($rows as $data) {
                 $morefields[$data['keyword']]=$data['data'];
            }
            $allfields = $f3->merge('currentvalues',$morefields);

            foreach($allfields as $key=>$val) {
                $input[$key] = isset($input[$this->field_map[$key]])?$input[$this->field_map[$key]]:$allfields[$key];
                if($key<>$this->field_map[$key]) { unset($input[$this->field_map[$key]]); }
            }

            // if no static member is set, populate with current
            if(!isset($input['static_members'])) {
                if(isset($this->staticmembers[$f3->get('PARAMS.id')])) {
                    $input['static_members']=$this->staticmembers[$f3->get('PARAMS.id')];
                } else {
                    $input['static_members']=array();
                }
            } 
            unset($input['member']);  // discard table entry as it will be processed specially in create_queue

            // if not dynamic members are specified, pass stored ones
            $astdb = $this->get_astdb_qpenalty();
            if(!isset($input['dynamic_members'])) {
                if(isset($astdb[$f3->get('PARAMS.id')]['agents'])) {
                    $input['dynamic_members']=$astdb[$f3->get('PARAMS.id')]['agents']; 
                } else {
                    $input['dynamic_members']=array();
                }
            } 

            $input['restrict_dynamic_agents'] = isset($input['restrict_dynamic_agents'])?$input['restrict_dynamic_agents']:$astdb[$f3->get('PARAMS.id')]['dynmemberonly'];

            $input['extension'] = $f3->get('PARAMS.id');

            $this->create_queue($f3, $input, 'UPDATE');

            $this->applyChanges($input);
        }

    }

    public function post($f3) {

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

        // Get next queue number from queues_config table, filling gaps if any
        $idfield = $this->id_field;
        $query = "SELECT cast($idfield AS unsigned)+1 AS extension FROM queues_config mo WHERE NOT EXISTS ";
        $query.= "(SELECT NULL FROM queues_config mi WHERE cast(mi.$idfield AS unsigned) = CAST(mo.$idfield AS unsigned)+ 1) ";
        $query.= "ORDER BY CAST($idfield AS unsigned) LIMIT 1";
        $rows  = $db->exec($query);
        $EXTEN = $rows[0]['extension'];
        if($EXTEN=='') { $EXTEN=$this->initial_exten_n; }
        $input['extension'] = $EXTEN;

        // Check if extension number is valid and it has no collitions
        $this->checkValidExtension($f3,$EXTEN);

        // Set default name if not specified
        $input['descr'] = isset($input['name'])?$input['name']:$input['extension'];
        unset($input['name']);

        // Create proper entries in DB and ASTDB
        $this->create_queue($f3, $input, 'INSERT');

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc    = $f3->get('REALM');
        header("Location: $loc/".$EXTEN, true, 201);
        die();

    }

    public function delete($f3) {

        $db  = $f3->get('DB');;

        // for queues we have two tables, queues_config and queues_details so we cannot rely only
        // on f3 abastraction classes

        // Delete requires and ID to be passed
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

            // Delete from queues table using SQL Mapper
            try {
                $this->data->erase($this->id_field."=".$oneid);
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

            // Delete all relevant ASTDB entries
            $this->ami->DatabaseDelTree('QPENALTY/'.$oneid);
        }

        // Delete data from queues_details table
        try {
            $db->exec("DELETE FROM queues_details WHERE id IN (?)",array(1=>$allids));
        } catch(\PDOException $e) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
            die();
        }

        $this->applyChanges($input);
    }

    private function get_astdb_qpenalty() {
        // Get ASTDB entries frokm QPENALTY and populate an array
        // (Used for dynamic member configuration)
        $astdb = array();
        $res = $this->ami->DatabaseShow('QPENALTY');
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            if($partes[3]=='agents') {
                $astdb[$partes[2]][$partes[3]][]=$partes[4].",".$val;
            } else {
                $astdb[$partes[2]][$partes[3]]=$val;
            }
        }
        return $astdb;
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

    private function expand_static_member($memb) {
        // Given a short member of type (prefix)number,penalty return the full channel/member
        // that will be used in the member table entry
        $member=$memb;
        if(preg_match("/^S([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'SIP/'.$matches[1].",".$matches[2];
        } else if(preg_match("/^X([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'IAX2/'.$matches[1].",".$matches[2];
        } else if(preg_match("/^Z([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'ZAP/'.$matches[1].",".$matches[2];
        } else if(preg_match("/^D([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'DAHDI/'.$matches[1].",".$matches[2];
        } else if(preg_match("/^A([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'AGENT/'.$matches[1].",".$matches[2];
        } else if(preg_match("/^([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'Local/'.$matches[1]."@from-queue/n,".$matches[2];
        }
        return $member;
    }

    private function reduce_static_member($memb) {
        // Given the full queue member name stored in the member field in queues_details
        // return the short notation for IssabelPBX
        $member=$memb;
        if(preg_match("/^Local\/([^@]*)[^,]*,(.*)/i",$memb, $matches)) {
            $member = $matches[1].",".$matches[2];
        } else if(preg_match("/^SIP\/([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'S'.$matches[1].",".$matches[2];
        } else if(preg_match("/^IAX\/([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'X'.$matches[1].",".$matches[2];
        } else if(preg_match("/^ZAP\/([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'Z'.$matches[1].",".$matches[2];
        } else if(preg_match("/^DAHDI\/([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'D'.$matches[1].",".$matches[2];
        } else if(preg_match("/^AGENT\/([^,]*),(.*)/i",$memb, $matches)) {
            $member = 'A'.$matches[1].",".$matches[2];
        }
        return $member;
    }

    private function checkValidExtension($f3,$extension) {

        $db = $f3->get('DB');

        if(in_array($extension,$this->alldestinations)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 409 Conflict', true, 409);
            die();
        }

        return true;

    }

}


