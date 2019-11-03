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
  $Id: queues.php, Tue 04 Sep 2018 09:54:37 AM EDT, nicolas@issabel.com
*/

class queues extends rest {

    protected $table           = "queues_config";
    protected $id_field        = 'extension';
    protected $name_field      = 'descr';
    protected $extension_field = 'extension';
    protected $list_fields     = array('timeout','strategy');
    protected $initial_exten_n = '2000';
    protected $alldestinations = array();
    protected $search_field    = 'descr';
    protected $allextensions   = array();
    protected $staticmembers   = array();

    protected $provides_destinations = true;
    protected $context               = 'ext-queues';
    protected $category              = 'Queues';

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
        'cron_minute',
        'cron_hour',
        'cron_dow',
        'cron_dom',
        'cron_month',
        'cron_random',
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
        'dest'                         => 'failover_destination',
        'destcontinue'                 => 'continue_destination',
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
        'monitor-format'               => 'recording_format',
        'monitor-join'                 => 'monitor_join',
        'monitor_heard'                => 'caller_volume_adjustment',
        'monitor_spoken'               => 'agent_volume_adjustment',
        'monitor_type'                 => 'recording_mode',
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
        'timeoutpriority'              => 'max_wait_mode',
        'timeoutrestart'               => 'timeout_restart',
        'togglehint'                   => 'generate_device_hints',
        'wrapuptime'                   => 'wrapup_time',
        'extension'                    => 'extension',
        'descr'                        => 'name',
        'password'                     => 'password',
        'timeout'                      => 'timeout',
        'weight'                       => 'weight',
        'cron_minute'                  => 'cron_minute',
        'cron_hour'                    => 'cron_hour',
        'cron_dom'                     => 'cron_dom',
        'cron_dow'                     => 'cron_dow',
        'cron_month'                   => 'cron_month',
        'cron_random'                  => 'cron_random',
    );

    protected $validations = array(
        'ring_strategy'         => array('ringall','leastrecent','fewestcalls','random','rrmemory','rrordered','linear','wrandom'),
        'join_emmpty'           => array('yes','no','strict','penalty,paused,invalid,unavailable,inuse,ringing','loose'),
        'leave_when_emmpty'     => array('yes','no','strict','penalty,paused,invalid,unavailable,inuse,ringing','loose'),
        'auto_pause'            => array('yes','no','all'),
        'music_on_hold_ringing' => array(0,1,2),
        'skip_join_announce'    => array(0,1,2),
        'cron_dow'              => 'checkCronDow',
        'cron_dom'              => 'checkCronDom',
        'cron_minute'           => 'checkCronMinute',
        'cron_hour'             => 'checkCronHour',
        'cron_month'            => 'checkCronMonth',
    );

    protected $transforms = array(
        'generate_device_hints'                => 'enabled',
        'call_confirm'                         => 'enabled',
        'wait_time_prefix'                     => 'enabled',
        'restrict_dynamic_agents'              => 'enabled',
        'agent_restrictions'                   => 'agent_restrictions',
        'skip_busy_agents'                     => 'skip_busy_agents',
        'music_on_hold_ringing'                => 'music_on_hold_ringing',
        'recording_mode'                       => 'recording_mode',
        'answered_elsewhere'                   => 'enabled',
        'max_wait_mode'                        => 'max_wait_mode',
        'cron_random'                          => 'cron_random',
    );

    protected $presentationTransforms = array(
        'generate_device_hints'                => 'presentation_enabled',
        'call_confirm'                         => 'presentation_enabled',
        'wait_time_prefix'                     => 'presentation_enabled',
        'restrict_dynamic_agents'              => 'presentation_enabled',
        'agent_restrictions'                   => 'presentation_agent_restrictions',
        'skip_busy_agents'                     => 'presentation_skip_busy_agents',
        'music_on_hold_ringing'                => 'presentation_music_on_hold_ringing',
        'recording_mode'                       => 'presentation_recording_mode',
        'answered_elsewhere'                   => 'presentation_enabled',
        'max_wait_mode'                        => 'presentation_max_wait_mode',
        'cron_random'                          => 'presentation_cron_random',
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,1,1);

        $alldest = new extensions($f3);
        $this->allextensions = $alldest->getExtensions($f3);

        // Static members 
        $rows = $this->db->exec("SELECT id AS exten,GROUP_CONCAT(data separator '^') as member FROM queues_details WHERE keyword='member' GROUP BY id");
        foreach($rows as $row) {
            $this->staticmembers[$row['exten']]=$row['member'];
        }
    }

    private function create_queue($f3,$post,$method='INSERT') {

        $errors = array();
        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $EXTEN = ($method=='INSERT')?$post['extension']:$f3->get('PARAMS.id');

        // check to see if static member list has valid extension numbers and bails out if one invalid is found 
        if(isset($post['static_members'])) {
            foreach($post['static_members'] as $thisexten) {
                list($exten,$penalty) = preg_split("/,/",$thisexten);
                $output = preg_replace( '/[^0-9]/', '', $exten );
                if(!in_array($output,$this->allextensions)) {
                    $errors[]=array('status'=>'422','source'=>'static_members','detail'=>'Inexistent extension found in list');
                    $this->dieWithErrors($errors);
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
                    $errors[]=array('status'=>'422','source'=>'dynamic_members','detail'=>'Inexistent extension found in list');
                    $this->dieWithErrors($errors);
                } else {
                    $amidb[] = "QPENALTY/$EXTEN/agents:$exten:$penalty";
                }
            }
        }

        // check cron_schedule value and remove other entires that we do not want inserted depending on its value
        if($post['cron_schedule']<>'custom') {
            unset($post['cron_dom']);
            unset($post['cron_hour']);
            unset($post['cron_dow']);
            unset($post['cron_month']);
            unset($post['cron_minute']);
            if($post['cron_schedule']=='never' || $post['cron_schedule']=="reboot") {
                unset($post['cron_random']);
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
                $msg  = $e->getMessage();
                $code = $e->getCode();
                $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                $this->dieWithErrors($errors);
            }

            foreach($this->details_fields as $field) {
                $keyword  = $field;
                $def      = isset($this->default[$field])?$this->default[$field]:'';
                $data     = isset($post[$field])?$post[$field]:$def;
                $query    = 'INSERT INTO queues_details (id,keyword,data) VALUES (?,?,?)';
                
                try {
                    $db->exec($query, array($EXTEN,$keyword,$data));
                } catch(\PDOException $e) {
                    $msg  = $e->getMessage();
                    $code = $e->getCode();
                    $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                    $this->dieWithErrors($errors);
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
                $msg  = $e->getMessage();
                $code = $e->getCode();
                $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                $this->dieWithErrors($errors);
            }

            // Update queues_details table
            $db->exec("DELETE FROM queues_details WHERE id=?",array($EXTEN));

            foreach($flddetails as $keyword=>$data) {
                $query = 'INSERT INTO queues_details (id,keyword,data) VALUES (?,?,?)';
                try {
                    $db->exec($query,array($EXTEN,$keyword,$data));
                } catch(\PDOException $e) {
                    $msg  = $e->getMessage();
                    $code = $e->getCode();
                    $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                    $this->dieWithErrors($errors);
                }
            }
        }

        // Set static members on queues_details
        $alreadyset=array();
        foreach($post['static_members'] as $data) {
            $data = $this->expand_static_member($data);
            if(!in_array($data,$alreadyset)) {  // prevent a duplicate agent passed to trigger a 500 error
                $query = 'INSERT INTO queues_details (id,keyword,data) VALUES (?,?,?)';
                try {
                    $db->exec($query,array($EXTEN,'member',$data));
                } catch(\PDOException $e) {
                    $msg  = $e->getMessage();
                    $code = $e->getCode();
                    $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                    $this->dieWithErrors($errors);
                }
            }
            $alreadyset[]=$data;
        }

        $ami->DatabaseDelTree('QPENALTY/'.$EXTEN);
        foreach($amidb as &$valor) {
            list ($family,$key,$value) = preg_split("/:/",$valor,3);
            $ami->DatabaseDel($family,$key);
            $ami->DatabasePut($family,$key,$value);
        }
    }

    public function get($f3, $from_child=0) {

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

        $astdb = $this->get_astdb_qpenalty($f3);

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
            $rows[$idx] = $this->presentationTransformValues($f3,$rows[$idx]);
        }

        if($f3->get('PARAMS.id')=='') {
            // for collection show only listed or queried fields
            parse_str($f3->QUERY, $qparams);
            if(isset($qparams['fields'])) {
                if($qparams['fields']=='*') {
                    $otherfields = array_merge($this->config_fields,$this->details_fields);
                } else {
                    $otherfields = $f3->split($qparams['fields']);
                }
            }
            $otherfields[]='extension';
            $otherfields[]='name';
            $otherfields[] = 'static_members';
            $otherfields[] = 'dynamic_members';

            foreach($otherfields as $key) {
                $otherfields[] = isset($this->field_map[$key])?$this->field_map[$key]:$key;
            }

            $listfields=array();
            foreach($this->list_fields as $key) {
                $listfields[] = isset($this->field_map[$key])?$this->field_map[$key]:$key;
            }
            $allfields = array_merge($listfields,$otherfields);

            foreach($rows as $idx=>$row) {
                foreach($row as $key=>$val) {
                    if(!in_array($key,$allfields)) {
                        unset($rows[$idx][$key]);
                    }
                }
            }
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    public function search($f3, $from_child=0) {

        // SEARCH record or collection based on GET function

        if($f3->get('PARAMS.term')=='') {
            $errors[]=array('status'=>'405','detail'=>'Search term not provided');
            $this->dieWithErrors($errors);
        }

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
        $query = "SELECT ".$this->id_field." AS extension, ".$this->name_field." AS name, $tablef, $joinedf FROM queues_config $joinedj WHERE ".$this->search_field." LIKE ?";

        $astdb = $this->get_astdb_qpenalty($f3);

        $rows = $db->exec($query,array("%".$f3->get('PARAMS.term')."%"));

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
            $rows[$idx] = $this->presentationTransformValues($f3,$rows[$idx]);
        }

        if($f3->get('PARAMS.id')=='') {
            // for collection show only listed or queried fields
            parse_str($f3->QUERY, $qparams);
            if(isset($qparams['fields'])) {
                if($qparams['fields']=='*') {
                    $otherfields = array_merge($this->config_fields,$this->details_fields);
                } else {
                    $otherfields = $f3->split($qparams['fields']);
                }
            }
            $otherfields[]='extension';
            $otherfields[]='name';
            $otherfields[] = 'static_members';
            $otherfields[] = 'dynamic_members';

            foreach($otherfields as $key) {
                $otherfields[] = isset($this->field_map[$key])?$this->field_map[$key]:$key;
            }

            $listfields=array();
            foreach($this->list_fields as $key) {
                $listfields[] = isset($this->field_map[$key])?$this->field_map[$key]:$key;
            }
            $allfields = array_merge($listfields,$otherfields);

            foreach($rows as $idx=>$row) {
                foreach($row as $key=>$val) {
                    if(!in_array($key,$allfields)) {
                        unset($rows[$idx][$key]);
                    }
                }
            }
        }

        $this->outputSuccess($rows);
    }

    public function put($f3,$from_child) {

        $errors = array();
        $db = $f3->get('DB');

        // Expect JSON data, if its not good, fail
        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error();
            $errors[]=array('status'=>'400','detail'=>'Could not decode JSON','code'=>$error);
            $this->dieWithErrors($errors);
        }

        // Put *requires* and id resource to be SET, as it will be used to update or insert with specified id
        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $EXTEN = $f3->get('PARAMS.id');
        $this->data->load(array($this->id_field.'=?',$EXTEN));

        if ($this->data->dry()) {

            // No entry with that extension/id, this is an INSERT, extension number is the one in the URL

            $this->dieExtensionDuplicate($f3,$f3->get('PARAMS.id'));

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

            $input = $this->flatten($input);
            $input = $this->transformValues($f3,$input);
            $input = $this->validateValues($f3,$input);
            $input = $this->unflatten($input);

            foreach($allfields as $key=>$val) {
                $input[$key] = isset($input[$this->field_map[$key]])?$input[$this->field_map[$key]]:$allfields[$key];
                if($key<>$this->field_map[$key]) { unset($input[$this->field_map[$key]]); }
            }

            // if no static member is set, populate with current
            if(!isset($input['static_members'])) {
                if(isset($this->staticmembers[$f3->get('PARAMS.id')])) {
                    $member_string = $this->staticmembers[$f3->get('PARAMS.id')];
                    $membs = explode("^",$member_string);
                    $input['static_members']=array_map(array($this,'reduce_static_member'),$membs);
                } else {
                    $input['static_members']=array();
                }
            } 
            unset($input['member']);  // discard table entry as it will be processed specially in create_queue

            // if not dynamic members are specified, pass stored ones
            $astdb = $this->get_astdb_qpenalty($f3);
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

    public function post($f3, $from_child=0) {

        $errors = array();
        $db = $f3->get('DB');

        // Expect JSON data, if its not good, fail
        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error();
            $errors[]=array('status'=>'400','detail'=>'Could not decode JSON','code'=>$error);
            $this->dieWithErrors($errors);
        }

        // If post has an ID, fail, it will create a new resource with next available id
        if($f3->get('PARAMS.id')!='') {
            $errors[]=array('status'=>'400','detail'=>'We refuse to insert a record if a resource id is passed. For update use the PUT method instead.');
            $this->dieWithErrors($errors);
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
        $this->dieExtensionDuplicate($f3,$EXTEN);

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

    public function delete($f3,$from_child) {

        $errors    = array();
        $db  = $f3->get('DB');;
        $ami = $f3->get('AMI');;

        // for queues we have two tables, queues_config and queues_details so we cannot rely only
        // on f3 abastraction classes

        // Delete requires and ID to be passed
        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Cannot delete if no ID is supplied');
            $this->dieWithErrors($errors);
        }

        $input = json_decode($f3->get('BODY'),true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error();
            $errors[]=array('status'=>'400','detail'=>'Could not decode JSON','code'=>$error);
            $this->dieWithErrors($errors);
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            $this->data->load(array($this->id_field.'=?',$oneid));

            if($this->data->dry()) {
                $errors[]=array('status'=>'404','detail'=>'Could not find a record to delete');
                $this->dieWithErrors($errors);
            }

            // Delete from queues table using SQL Mapper
            try {
                $this->data->erase($this->id_field."=".$oneid);
            } catch(\PDOException $e) {
                $msg  = $e->getMessage();
                $code = $e->getCode();
                $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
                $this->dieWithErrors($errors);
            }

            // Delete all relevant ASTDB entries
            $ami->DatabaseDelTree('QPENALTY/'.$oneid);
        }

        // Delete data from queues_details table
        try {
            $db->exec("DELETE FROM queues_details WHERE id IN (?)",array(1=>$allids));
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'500','detail'=>$msg, 'code'=>$code);
            $this->dieWithErrors($errors);
        }

        $this->applyChanges($input);
    }

    private function get_astdb_qpenalty($f3) {
        // Get ASTDB entries frokm QPENALTY and populate an array
        // (Used for dynamic member configuration)
        $ami  = $f3->get('AMI');;
        $astdb = array();
        $res = $ami->DatabaseShow('QPENALTY');
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

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }

    public function agent_restrictions($data) {
        $rest = array('call_as_dialed'=>'0','no_followme_or_forward'=>'1','extensions_only'=>'2');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return '0';
    }

    public function presentation_agent_restrictions($data) {
        $rest = array('0'=>'call_as_dialed','1'=>'no_followme_or_forward','2'=>'extensions_only');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return $rest['0'];
    }

    public function skip_busy_agents($data) {
        $rest = array('no'=>'0','yes'=>'1','yes_ringinuse_no'=>'2','queue_calls_only'=>'3');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return '0';
    }

    public function presentation_skip_busy_agents($data) {
        $rest = array('0'=>'no','1'=>'yes','2'=>'yes_ringinuse_no','3'=>'queue_calls_only');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return $rest['0'];
    }

    public function music_on_hold_ringing($data) {
        $rest = array('moh_only'=>'0','ring_only'=>'1','agent_ringing'=>'2');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return '0';
    }

    public function presentation_music_on_hold_ringing($data) {
        $rest = array('0'=>'moh_only','1'=>'ring_only','2'=>'agent_ringing');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return $rest['0'];
    }

    public function recording_mode($data) {
        $rest = array('include_hold_time'=>'','after_answered'=>'b');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return '0';
    }

    public function presentation_recording_mode($data) {
        $rest = array(''=>'include_hold_time','b'=>'after_answered');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return $rest['0'];
    }

    public function max_wait_mode($data) {
        $rest = array('strict'=>'app','loose'=>'conf');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return '0';
    }

    public function presentation_max_wait_mode($data) {
        $rest = array('app'=>'strict','loose'=>'conf');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return $rest['0'];
    }

    public function cron_random($data) {
        $rest = array('yes'=>'true','no'=>'false');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return '0';
    }

    public function presentation_cron_random($data) {
        $rest = array('true'=>'yes','false'=>'no',''=>'no');
        if(array_key_exists($data,$rest)) {
            return $rest[$data];
        }
        return $rest['0'];
    }

    public function checkCronDow($data,$field,&$errors) {

        foreach($data as $valor) {
            if(is_numeric($valor) && intval($valor)>=0 && intval($valor)<=6) {
               //
            } else {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Valid range: 0-6');
            }
        }
        return implode(",",$data);
    }

    public function checkCronDom($data,$field,&$errors) {
        foreach($data as $valor) {
            if(is_numeric($valor) && intval($valor)>=1 && intval($valor)<=31) {
               //
            } else {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Valid range: 1-31');
            }
        }
        return implode(",",$data);
    }

    public function checkCronMinute($data,$field,&$errors) {
        foreach($data as $valor) {
            if(is_numeric($valor) && intval($valor)>=0 && intval($valor)<=59) {
               //
            } else {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Valid range: 0-59');
            }
        }
        return implode(",",$data);
    }

    public function checkCronHour($data,$field,&$errors) {
        foreach($data as $valor) {
            if(is_numeric($valor) && intval($valor)>=0 && intval($valor)<=23) {
               //
            } else {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Valid range: 0-23');
            }
        }
        return implode(",",$data);
    }

    public function checkCronMonth($data) {
        foreach($data as $valor) {
            if(is_numeric($valor) && intval($valor)>=1 && intval($valor)<=12) {
               //
            } else {
                $errors[]=array('status'=>'422','source'=>$field,'detail'=>'Valid range: 1-12');
            }
        }
        return implode(",",$data);
    }

}


