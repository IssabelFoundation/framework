<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2019 Issabel Foundation                                |
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
  $Id: extensions.php, Fri 05 Apr 2019 05:51:34 PM EDT, nicolas@issabel.com
*/

class extensions extends rest {
    protected $table           = "users";
    protected $id_field        = 'extension';
    protected $name_field      = 'name';
    protected $extension_field = 'extension';
    protected $dest_field      = 'CONCAT("from-internal",",",extension,",1")';
    protected $list_fields     = array('extension','name','tech','dial','secret','context');
    protected $extension_limit = 500;

    protected $field_map = array(
        'tech'                                  => 'tech',
        'sipname'                               => 'sip_alias',
        'astdb.ampusers.language'               => 'language',
        'emergency_cid'                         => 'callerid_override.emergency',
        'outboundcid'                           => 'callerid_override.outbound',
        'astdb.ampusers.cidnum'                 => 'callerid_override.internal',
        'noanswer_cid'                          => 'destination.callerid_prefix_no_answer',
        'busy_cid'                              => 'destination.callerid_prefix_busy',
        'chanunavail_cid'                       => 'destination.callerid_prefix_unavailable',
        'noanswer_dest'                         => 'destination.no_answer',
        'busy_dest'                             => 'destination.busy',
        'chanunavail_dest'                      => 'destination.unavailable',
        'astdb.ampusers.answermode'             => 'extension_options.internal_auto_answer',
        'parameters.callgroup'                  => 'device_options.call_group',
        'parameters.dtmfmode'                   => 'device_options.dtmf_mode',
        'astdb.cw'                              => 'extension_options.call_waiting',
        'parameters.qualifyfreq'                => 'device_options.qualify_frequency',
        'parameters.icesupport'                 => 'device_options.ice_support',
        'parameters.dtlsenable'                 => 'device_options.dtls_enable',
        'parameters.dtlssetup'                  => 'device_options.dtls_setup',
        'parameters.dtlsverify'                 => 'device_options.dtls_verify',
        'parameters.dtlscertfile'               => 'device_options.dtls_certificate_file',
        'parameters.dtlsprivatekey'             => 'device_options.dtls_private_key',
        'parameters.allow'                      => 'device_options.allow_codecs',
        'parameters.disallow'                   => 'device_options.disallow_codecs',
        'parameters.deny'                       => 'device_options.deny_acl',
        'parameters.permit'                     => 'device_options.permit_acl',
        'astdb.ampusers.queues/qnostate'        => 'extension_options.queue_state_detection',
        'parameters.sendrpid'                   => 'device_options.send_rpid',
        'parameters.trustrpid'                  => 'device_options.trust_rpid',
        'parameters.pickupgroup'                => 'device_options.pickup_group',
        'astdb.ampusers.dialopts'               => 'extension_options.dial_options',
        'astdb.ampusers.cfringtimer'            => 'extension_options.call_forward_ring_time',
        'ringtimer'                             => 'extension_options.ring_time',
        'astdb.ampusers.concurrency_limit'      => 'extension_options.outbound_concurrency_limit',
        'astdb.ampusers.screen'                 => 'extension_options.call_screening',
        'astdb.ampusers.pinless'                => 'extension_options.pinless_dialing',
        'astdb.ampusers.recording/in/external'  => 'recording.inbound_external',
        'astdb.ampusers.recording/in/internal'  => 'recording.inbound_internal',
        'astdb.ampusers.recording/out/external' => 'recording.outbound_external',
        'astdb.ampusers.recording/out/internal' => 'recording.outbound_internal',
        'astdb.ampusers.recording/ondemand'     => 'recording.ondemand',
        'astdb.ampusers.recording/priority'     => 'recording.priority',
        'parameters.accountcode'                => 'device_options.account_code',
        'parameters.canreinvite'                => 'device_options.can_reinvite',
        'parameters.host'                       => 'device_options.host',
        'parameters.type'                       => 'device_options.type',
        'parameters.nat'                        => 'device_options.nat',
        'parameters.port'                       => 'device_options.port',
        'parameters.qualify'                    => 'device_options.qualify',
        'parameters.transport'                  => 'device_options.transport',
        'parameters.avpf'                       => 'device_options.avpf',
        'parameters.force_avp'                  => 'device_options.force_avp',
        'parameters.rtcp_mux'                   => 'device_options.rtcp_mux',
        'parameters.encryption'                 => 'device_options.encryption',
        'parameters.immediate'                  => 'device_options.immediate',
        'parameters.signalling'                 => 'device_options.signalling',
        'parameters.echocancel'                 => 'device_options.echo_cancel',
        'parameters.echocancelwhenbirdged'      => 'device_options.echo_cancel_when_bridged',
        'parameters.echotraining'               => 'device_options.echo_training',
        'parameters.busydetect'                 => 'device_options.busy_detect',
        'parameters.busycount'                  => 'device_options.busy_count',
        'parameters.callprogress'               => 'device_options.call_progress',
        'parameters.requirecalltoken'           => 'device_options.require_call_token',
        'parameters.transfer'                   => 'device_options.transfer',
    );

    protected $validations = array(
        'tech'                                         => array('sip','pjsip','iax2','virtual','dahdi','custom'),
        'recording.inbound_internal'                   => array('dontcare','always','never'),
        'recording.inbound_external'                   => array('dontcare','always','never'),
        'recording.outbound_internal'                  => array('dontcare','always','never'),
        'recording.outbound_external'                  => array('dontcare','always','never'),
        'device_options.nat'                           => array('yes','no','never','route'),
        'device_options.transfer'                      => array('yes','no'),
        'device_options.avpf'                          => array('yes','no'),
        'device_options.force_avp'                     => array('yes','no'),
        'device_options.ice_support'                   => array('yes','no'),
        'device_options.dtls_enable'                   => array('yes','no'),
        'device_options.dtls_verify'                   => array('yes','no','fingerprint'),
        'device_options.dtls_setup'                    => array('actpass','active','passive'),
        'device_options.rtcp_mux'                      => array('yes','no'),
        'device_options.encryption'                    => array('yes','no'),
        'device_options.call_progress'                 => array('yes','no'),
        'device_options.busy_detect'                   => array('yes','no'),
        'device_options.busy_count'                    => 'check_digit',
        'device_options.can_reinvite'                  => array('yes','no','nonat','update'),
        'device_options.require_call_token'            => array('yes','no','auto'),
        'device_options.dtmf_mode'                     => array('rfc2833','inband','auto','info','shortinfo'),
        'extension_options.pinless_dialing'            => array('','NOPASSWD'),
        'extension_options.call_waiting'               => array('','ENABLED'),
        'extension_options.outbound_concurrency_limit' => 'check_digit',
        'device_options.send_rpid'                     => array('yes','no','pai'),
        'device_options.trust_rpid'                    => array('yes','no'),
        'callerid_override.emergency'                  => 'check_digit',
        'callerid_override.outbound'                   => 'check_digit',
        'callerid_override.internal'                   => 'check_digit',
        'recording.priority'                           => 'check_digit',
        'device_options.transport'                     => 'check_transport',
        'device_options.permit_acl'                    => 'check_acl',
        'device_options.deny_acl'                      => 'check_acl',
    );

    protected $transforms = array(
        'extension_options.pinless_dialing'       => 'transformPinless',
        'extension_options.call_waiting'          => 'transformCallWaiting',
        'extension_options.internal_auto_answer'  => 'transformAutoAnswer',
        'extension_options.queue_state_detection' => 'transformQueueState',
        'recording.ondemand'                      => 'transformOndemand',
    );

    protected $presentation_transforms = array(
        'extension_options.pinless_dialing'       => 'presentationTransformPinless',
        'extension_options.call_waiting'          => 'presentationTransformCallWaiting',
        'extension_options.internal_auto_answer'  => 'presentationTransformAutoAnswer',
        'extension_options.queue_state_detection' => 'presentationTransformQueueState',
        'recording.ondemand'                      => 'presentationTransformOndemand',
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

        if(is_readable('/var/www/db/extlimit')) {
            $this->extension_limit = intval(file_get_contents("/var/www/db/extlimit"));
        }

        $rows = $this->db->exec("SELECT * FROM alldestinations");
        foreach($rows as $row) {
            $this->alldestinations[]=$row['extension'];
            if($row['type']=='extension') {
                $this->allextensions[]=$row['extension'];
            }
        }

    }

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');
        $rows = array();

        // GET record or collection

        // sip
        $query=  "SELECT a.extension,b.description AS name,b.tech,b.dial,c.data AS secret,d.data AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest,ringtimer FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN sip c ON a.extension=c.id LEFT JOIN sip d ON a.extension=d.id WHERE c.keyword='secret' AND d.keyword='context' {INDIVIDUAL} ";
        $query.= "UNION ";
        // iax
        $query.=  "SELECT a.extension,b.description AS name,b.tech,b.dial,c.data AS secret,d.data AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest,ringtimer FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN iax c ON a.extension=c.id LEFT JOIN iax d ON a.extension=d.id WHERE c.keyword='secret' AND d.keyword='context' {INDIVIDUAL}";
        $query.= "UNION ";
        // dahdi 
        $query.=  "SELECT a.extension,b.description AS name,b.tech,b.dial,'' AS secret,c.data AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest,ringtimer FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN dahdi c ON a.extension=c.id WHERE c.keyword='context' {INDIVIDUAL} ";
        $query.= "UNION ";
        // custom
        $query.=  "SELECT a.extension,b.description AS name,b.tech,b.dial,'' AS secret,'' AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest,ringtimer FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id WHERE tech='custom' {INDIVIDUAL} ";
        $query.= "UNION ";
        // virtual
        $query.=  "SELECT a.extension,b.description AS name,'virtual' AS tech,'' AS  dial,'' AS secret,'' AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest,ringtimer FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id WHERE tech is NULL {INDIVIDUAL} ";

        $query.= "ORDER BY 1";

        // check if we have fields specified
        parse_str($f3->QUERY, $qparams);
        if(isset($qparams['fields'])) { $extrafields = $qparams['fields']; } else { $extrafields=''; }

        if($f3->get('PARAMS.id')=='') {
            // collection
            $query = preg_replace("/{INDIVIDUAL}/","",$query);
            $rows = $db->exec($query);
        } else {
            // individual record
            $id    = $f3->get('PARAMS.id');
            $query = preg_replace("/{INDIVIDUAL}/","AND a.extension='$id'",$query);
            $rows = $db->exec($query);
            $extrafields='*'; // selecting one record will display all available fields always
        }

        $result=array();
        foreach($rows as $idx=>$data) {

            $allextrafields = explode(',',$extrafields);

            $techtable = strtolower($data['tech']);
            if($techtable=='iax2') { $techtable='iax'; }

            $final = array();

            if($techtable<>'custom' && $techtable<>'virtual') {  // custom extension do not have a table to get extra data from
                $query = "SELECT keyword,data FROM ".$techtable." WHERE id=:id AND keyword NOT IN ('secret','context','dial') ORDER BY flags";
                $rews = $db->exec($query,array(':id'=>$data['extension']));

                foreach($rews as $ii=>$dd) { 
                    if(array_key_exists('parameters.'.$dd['keyword'],$this->field_map)) {
                        $index = $this->field_map['parameters.'.$dd['keyword']];
                    } else {
                        $index = 'device_options.'.$dd['keyword'];
                    }
                    $final[$index]=$dd['data']; 
                }
            }

            $final['extension_options.pinless_dialing']='';  // if disabled it has no entry, so asume it empty
            $final['extension_options.call_waiting']='';  // if disabled it has no entry, so asume it empty

            $res = $this->ami->DatabaseShow('AMPUSER/'.$data['extension']);
            foreach($res as $key=>$val) {
                $partes = preg_split("/\//",$key);
                array_shift($partes);
                array_shift($partes);
                array_shift($partes);
                $astdbkey = implode("/",$partes);
                if($astdbkey=='outboundcid') { continue; } // we already have this in the users table
                if($astdbkey=='device')      { continue; } // we wont use user and device mode in api
                if($astdbkey=='noanswer')    { continue; } // not used anymore? 

                if(array_key_exists('astdb.ampusers.'.$astdbkey, $this->field_map)) {
                    $reskey = $this->field_map['astdb.ampusers.'.$astdbkey];
                    $final[$reskey]=$val;
                } 
            }

            $res = $this->ami->DatabaseShow('CW/'.$data['extension']);
            foreach($res as $key=>$val) { 
                $final[$this->field_map['astdb.cw']]=$val; 
            }

            // field map on users table
            foreach($rows[$idx] as $key=>$val) {
                if(array_key_exists($key,$this->field_map)) {
                    unset($rows[$idx][$key]);
                    $rows[$idx][$this->field_map[$key]]=$val;
                }
            }

            $complete_array = array_merge($rows[$idx],$final); 

            $complete_array = $this->presentation_transform_values($f3,$complete_array);

            // apply dicitionary/map on field names (unflatten)
            foreach($complete_array as $key=>$val) {
                $partes = explode(".",$key);
                if(count($partes)>1) {
                    unset($complete_array[$key]);
                    if(!isset($complete_array[$partes[0]])) { $complete_array[$partes[0]]=array(); }
                    $complete_array[$partes[0]][$partes[1]]=$val;
                }
            }

            $print_fields = array_merge($this->list_fields,$allextrafields);

            // remove not asked fields from collection results
            if($f3->get('PARAMS.id')=='') {
               if($extrafields<>'*') {
                   // final has both tech table + astdb entries, if collection filter out unless field requested
                   foreach($complete_array as $key=>$val) {
                       if(is_array($val)) {
                           foreach($val as $subkey=>$subval) {
                               if(!in_array($key.".".$subkey,$print_fields)) {
                                   unset($complete_array[$key][$subkey]);
                               } 
                           } 

                       } else {
                           if(!in_array($key,$print_fields)) {
                               unset($complete_array[$key]);
                           } 
                       }
                   }
                   if(count($complete_array['device_options'])==0) {
                       unset($complete_array['device_options']);
                   }
                   if(count($complete_array['extension_options'])==0) {
                       unset($complete_array['extension_options']);
                   }
                   if(count($complete_array['recording'])==0) {
                       unset($complete_array['recording']);
                   }
                   if(count($complete_array['callerid_override'])==0) {
                       unset($complete_array['callerid_override']);
                   }
                   if(count($complete_array['destination'])==0) {
                       unset($complete_array['destination']);
                   }
               }
            }


            $result[]=$complete_array;
        }

        // final json output
        $final = array();
        $final['results'] = $result;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();
    }

    private function create_user($f3,$post,$method='INSERT') {

        $db = $f3->get('DB');

        $EXTEN = ($method=='INSERT')?$post['extension']:$f3->get('PARAMS.id');
        $NAME  = isset($post['name'])?$post['name']:$EXTEN;

        if(!isset($post['voicemail'])) {
            $VOICEMAIL='novm';
        } else {
            $VOICEMAIL=$post['voicemail'];
            if($VOICEMAIL<>'novm' || $VOICEMAIL<>'default') {
                $VOICEMAIL='novm';
            }
        }

        $RINGTIME    = isset($post['extension_options.ring_time'])?$post['extension_options.ring_time']:0;
        $LANG        = isset($post['language'])?$post['language']:'';
        $CFRINGTIME  = isset($post['extension_options.call_forward_ring_time'])?$post['extension_options.call_forward_ring_time']:0;
        $CONCURRENCY = isset($post['extension_options.outbound_concurrency_limit'])?$post['extension_options.outbound_concurrency_limit']:0;
        $ANSWERMODE  = isset($post['extension_options.internal_auto_answer'])?$post['extension_options.internal_auto_answer']:'disabled';
        $OUTBOUNDCID = isset($post['callerid_override.outbound'])?$post['callerid_override.outbound']:'';
        $QSTATE      = isset($post['extension_options.queue_state_detection'])?$post['extension_options.queue_state_detection']:'usestate';
        $RECPRIO     = isset($post['recording.priority'])?$post['recording.priority']:0;
        $RECINEXT    = isset($post['recording.inbound_external'])?$post['recording.inbound_external']:'dontcare';
        $RECININT    = isset($post['recording.inbound_internal'])?$post['recording.inbound_internal']:'dontcare';
        $RECOUTEXT   = isset($post['recording.outbound_external'])?$post['recording.outbound_external']:'dontcare';
        $RECOUTINT   = isset($post['recording.outbound_internal'])?$post['recording.outbound_internal']:'dontcare';
        $CALLWAIT    = isset($post['extension_options.call_waiting'])?$post['extension_options.call_waiting']:'ENABLED';
        $PINLESS     = isset($post['extension_options.pinless_dialing'])?$post['extension_options.pinless_dialing']:'';

        /*
        if($VOICEMAIL<>'novm') {
            exec("rm -f /var/spool/asterisk/voicemail/device/".$EXTEN);
            exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
        }
        */

        $astdb_defaults = array(
            "AMPUSER/$EXTEN:answermode:$ANSWERMODE",
            "AMPUSER/$EXTEN:cfringtimer:$CFRINGTIME",
            "AMPUSER/$EXTEN:cidname:$NAME",
            "AMPUSER/$EXTEN:cidnum:$EXTEN",
            "AMPUSER/$EXTEN:concurrency_limit:$CONCURRENCY",
            "AMPUSER/$EXTEN:device:$EXTEN",
            "AMPUSER/$EXTEN:language:$LANG",
            "AMPUSER/$EXTEN:noanswer:''",
            "AMPUSER/$EXTEN:outboundcid:$OUTBOUNDCID",
            "AMPUSER/$EXTEN:password:''",
            "AMPUSER/$EXTEN:queues/qnostate:$QSTATE",
            "AMPUSER/$EXTEN:recording:''",
            "AMPUSER/$EXTEN:pinless:$PINLESS",
            "AMPUSER/$EXTEN:recording/in/external:$RECINEXT",
            "AMPUSER/$EXTEN:recording/in/internal:$RECININT",
            "AMPUSER/$EXTEN:recording/ondemand:disabled",
            "AMPUSER/$EXTEN:recording/out/external:$RECOUTEXT",
            "AMPUSER/$EXTEN:recording/out/internal:$RECOUTINT",
            "AMPUSER/$EXTEN:recording/priority:$RECPRIO",
            "AMPUSER/$EXTEN:ringtimer:$RINGTIME",
            "AMPUSER/$EXTEN:voicemail:$VOICEMAIL",
            "CW:$EXTEN:$CALLWAIT",
        );

        // map from astdb family/key pairs to flattened input parameters
        $astdb_input_map = array(
            "AMPUSER/$EXTEN/outboundcid"              => 'callerid_override.outbound',
            "AMPUSER/$EXTEN/recording/in/external"    => 'recording.inbound_external',
            "AMPUSER/$EXTEN/recording/in/internal"    => 'recording.inbound_internal',
            "AMPUSER/$EXTEN/recording/out/external"   => 'recording.outbound_external',
            "AMPUSER/$EXTEN/recording/out/internal"   => 'recording.outbound_internal',
            "AMPUSER/$EXTEN/recording/priority"       => 'recording.priority',
            "AMPUSER/$EXTEN/recording/ondemand"       => 'recording.ondemand',
            "AMPUSER/$EXTEN/ringtimer"                => 'extension_options.ring_time',
            "AMPUSER/$EXTEN/answermode"               => 'extension_options.internal_auto_answer',
            "AMPUSER/$EXTEN/cfringtimer"              => 'extension_options.call_forward_ring_time',
            "AMPUSER/$EXTEN/cidname"                  => 'name',
            "AMPUSER/$EXTEN/voicemail"                => 'voicemail',
            "AMPUSER/$EXTEN/concurrency_limit"        => 'extension_options.outbound_concurrency_limit', 
            "AMPUSER/$EXTEN/dialopts"                 => 'extension_options.dial_options', 
            "AMPUSER/$EXTEN/language"                 => 'astdb.ampusers.language',
            "AMPUSER/$EXTEN/queues/qnostate"          => 'extension_options.queue_state_detection',
            "AMPUSER/$EXTEN/pinless"                  => 'extension_options.pinless_dialing',
            "CW/$EXTEN"                               => 'extension_options.call_waiting',
        );

        if($method=='INSERT') {

            $field_map_reverse = array_flip($this->field_map);
            foreach($post as $key=>$val) {
                if(array_key_exists($key,$field_map_reverse)) {
                     unset($post[$key]);
                     $post[$field_map_reverse[$key]]=$val;
                }
            }

            $defaults = array('ringtimer'=>'0', 'noanswer'=>'', 'recording'=>'', 'outboundcid'=>'', 'sipname'=>'', 'noanswer_cid'=>'', 'busy_cid'=>'', 'chanunavail_cid'=>'', 'noanswer_dest'=>'', 'busy_dest'=>'','chanunavail_dest'=>'');
            foreach($defaults as $key=>$val) {
                if(!isset($post[$key])) { $post[$key]=$val; }
            }

            $db->exec('INSERT INTO users(extension, name, voicemail, ringtimer, password, noanswer, recording, outboundcid, sipname, noanswer_cid, busy_cid, chanunavail_cid, noanswer_dest, busy_dest, chanunavail_dest) '.
                      'VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', 
                      array( $EXTEN, $NAME, $VOICEMAIL, $post['ringtimer'], '', '', '', $post['outboundcid'], $post['sipname'], $post['noanswer_cid'],
                             $post['busy_cid'], $post['chanunavail_cid'], $post['noanswer_dest'], $post['busy_dest'], $post['chanunavail_dest']));

            foreach($astdb_defaults as &$valor) {
                list ($family,$key,$value) = preg_split("/:/",$valor,3);
                if($value=="''") { $value=''; }
                $this->ami->DatabasePut($family,$key,$value);
            }

        } else {
            // UPDATE, if we have passed values, update ASTDB
            foreach($astdb_defaults as &$valor) {
                list ($family,$key,$value) = preg_split("/:/",$valor,3);
                $modkey = preg_replace("/\//","_",$key);
                if($value=="''") { $value=''; }
               
                $fk = $family."/".$key;
                if(array_key_exists($fk,$astdb_input_map)) {
                    if(isset($post[$astdb_input_map[$fk]])) {
                        $value = $post[$astdb_input_map[$fk]];
                        $this->ami->DatabaseDel($family,$key);
                        $this->ami->DatabasePut($family,$key,$value);
                    } 
                }
            }
        }
    }

    private function create_param($f3, $input, $method='INSERT') {

        $db = $f3->get('DB');
        $id = $f3->get('PARAMS.id');

        $EXTEN   = $input['extension'];
        $NAME    = $input['name'];
        $TECH    = strtolower($input['tech']);
        $DIAL    = isset($input['dial'])?$input['dial']:strtoupper($TECH)."/$EXTEN";
        $CONTEXT = isset($input['context'])?$input['context']:'from-internal';
        $SECRET  = isset($input['secret'])?$input['secret']:$this->generateRandomString(32);

        $defaults = array();

        if($TECH=='sip') {

            $defaults = array(
                array("secret",          "$SECRET",          2),
                array("dtmfmode",        "rfc2833",          3),
                array("canreinvite",     "no",               4),
                array("context",         "$CONTEXT",         5),
                array("host",            "dynamic",          6),
                array("trustrpid",       "yes",              7),
                array("sendrpid",        "no",               8),
                array("type",            "friend",           9),
                array("nat",             "yes",             10),
                array("port",            "5060",            11),
                array("qualify",         "yes",             12),
                array("qualifyfreq",     "60",              13),
                array("transport",       "udp",             14),
                array("avpf",            "no",              15),
                array("icesupport",      "no",              16),
                array("force_avp",       "no",              17),
                array("dtlsenable",      "no",              18),
                array("dtlsverify",      "no",              19),
                array("dtlssetup",       "actpass",         20),
                array("encryption",      "no",              21),
                array("callgroup",       "",                22),
                array("pickupgroup",     "",                23),
                array("disallow",        "",                24),
                array("allow",           "",                25),
                array("dial",            "SIP/$EXTEN",      26),
                array("accountcode",     "",                27),
                array("mailbox",         "$EXTEN@device",   28),
                array("deny",            "0.0.0.0/0.0.0.0", 29),
                array("permit",          "0.0.0.0/0.0.0.0", 30),
                array("account",         "$EXTEN",          31),
                array("callerid",        "device <$EXTEN>", 32),
                array("dtlscertfile",    "",                33),
                array("dtlsprivatekey",  "",                34)
            );


        } else if($TECH=='iax2') {

            $defaults = array(
                array("secret",           $SECRET,                            2),
                array("transfer",         "yes",                              3),
                array("context",          "$CONTEXT",                         4),
                array("host",             "dynamic",                          5),
                array("type",             "friend",                           6),
                array("port",             "4569",                             7),
                array("qualify",          "yes",                              8),
                array("disallow",         "all",                              9),
                array("allow",            "ulaw",                             10),
                array("dial",             "IAX2/$EXTEN",                      11),
                array("accountcode",      "",                                 12),
                array("mailbox",          "$EXTEN@device",                    13),
                array("deny",             "0.0.0.0/0.0.0.0",                  14),
                array("permit",           "0.0.0.0/0.0.0.0",                  15),
                array("requirecalltoken", "yes",                              16),
                array("account",          "208",                              17),
                array("callerid",         "device <$EXTEN>",                  18),
                array("setvar",           "REALCALLERIDNUM=$EXTEN",           19)
            );

        } else if($TECH=='dahdi') {

            $defaults = array(
                array("channel",               "1",                  0),
                array("context",               "$CONTEXT",           0),
                array("immediate",             "no",                 0),
                array("signalling",            "fxo_ks",             0),
                array("echocancel",            "yes",                0),
                array("echocancelwhenbridged", "no",                 0),
                array("echotraining",          "800",                0),
                array("busydetect",            "no",                 0),
                array("busycount",             "7",                  0),
                array("callprogress",          "no",                 0),
                array("dial",                  "DAHDI/1",            0),
                array("accountcode",           "",                   0),
                array("callgroup",             "",                   0),
                array("pickupgroup",           "",                   0),
                array("mailbox",               "$EXTEN@device",      0),
                array("account",               "$EXTEN",             0),
                array("callerid",              "device <$EXTEN>",    0)
            );

        }

        if($TECH<>'virtual') {
            if($method=='INSERT') {
                $db->exec('INSERT INTO devices (id,tech,dial,devicetype,user,description,emergency_cid) VALUES (?,?,?,?,?,?,?)',
                    array(1=>$EXTEN, 2=>$TECH, 3=>$DIAL, 4=>'fixed', 5=>$EXTEN, 6=>$NAME, 7=>$input['callerid_override.emergency']));
            } else {
                $db->exec('UPDATE devices SET tech=?,dial=?,devicetype=?,user=?,description=?,emergency_cid=? WHERE id=?',
                    array(1=>$TECH, 2=>$DIAL, 3=>'fixed', 4=>$EXTEN, 5=>$NAME, 6=>$input['callerid_override.emergency'], 7=>$EXTEN ));
            }
        }

        $techtable = $TECH;
        if($techtable=='iax2') { $techtable='iax'; }

        $current=array();
        if($method=='UPDATE' && $TECH<>'virtual' && $TECH<>'custom') {
            // when updating, read stored data from DB and overwrite only supplied data in request
            $rows = $db->exec("SELECT keyword,data FROM $techtable WHERE id=?",array(1=>$EXTEN));
            foreach($rows as $row) {
                $current[$row['keyword']]=$row['data'];
            }
        }

        foreach($defaults as &$valor) {

            if(isset($current[$valor[0]])) {
                // overwrite defaults with current data on update
                $valor[1]=$current[$valor[0]];
            }

            if(array_key_exists('parameters.'.$valor[0],$this->field_map)) {
                $newkey = $this->field_map['parameters.'.$valor[0]];
            } else {
                $newkey = 'device_options.'.$valor[0];
            }

            if(isset($input[$newkey])) {
                // we have a input with same field name, use it instead of default value for parameters techtable
                $valor[1]=$input[$newkey];
            } else {
                // secret is set on general settings, not device options
                if($valor[0]=='secret' && isset($input['secret'])) {
                    $valor[1] = $input['secret'];
                }
            }

            if($TECH<>'virtual' && $TECH<>'custom') {
                if($method=='INSERT') {
                    $db->exec("INSERT INTO $techtable (id,keyword,data,flags) VALUES (?,?,?,?)",
                        array(1=>$EXTEN,2=>$valor[0],3=>$valor[1],4=>$valor[2]));
                } else {
                    $db->exec("UPDATE $techtable SET data=?,flags=? WHERE id=? AND keyword=?",
                        array(1=>$valor[1],2=>$valor[2],3=>$EXTEN,4=>$valor[0]));
                }
            }
        }

        if($TECH<>'virtual') {
            if($method=='INSERT') {
                $amidb =  array(
                    "DEVICE/$EXTEN:default_user:$EXTEN",
                    "DEVICE/$EXTEN:dial:".strtoupper($TECH)."/$EXTEN",
                    "DEVICE/$EXTEN:type:fixed",
                    "DEVICE/$EXTEN:user:$EXTEN"
                );
                foreach($amidb as &$valor) {
                    list ($family,$key,$value) = preg_split("/:/",$valor);
                    $this->ami->DatabasePut($family,$key,$value);
                }
            } else {
                if($TECH=='custom') {
                    $this->ami->DatabaseDel("DEVICE/$EXTEN","dial");
                    $this->ami->DatabasePut("DEVICE/$EXTEN","dial",$DIAL);
                }
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

        // convert filed mapped names to real field names
        $field_map_reverse = array_flip($this->field_map);
        foreach($input as $key=>$val) {
            if(array_key_exists($key,$field_map_reverse)) {
                unset($input[$key]);
                $input[$field_map_reverse[$key]]=$val;
            } 
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

            if(!isset($input['tech'])) {
                $input['tech']='sip';
            }

            $TECH = strtolower($input['tech']);

            $this->checkValidExtension($f3,$f3->get('PARAMS.id'));

            // Default name if not supplied in data
            if(!isset($input['name'])) {
                $input['name'] = 'Extension';
            }

            $input['extension'] = $f3->get('PARAMS.id');

            // validate
            $input = $this->transform_values($f3,$input);
            $input = $this->validate_values($f3,$input);

            // Remove any stray DB entries in devices and sip tables
            if($TECH<>'virtual') {
                $db->exec("DELETE FROM devices WHERE id = ?", array(1=>$EXTEN));
            }

            $techtable = $TECH;
            if($techtable=='iax2') { $techtable='iax'; }

            if($techtable<>'virtual' && $techtable<>'custom') {
                $db->exec("DELETE FROM $techtable WHERE id = ?", array(1=>$EXTEN));
            }

            // flatten
            foreach($input as $key=>$val) {
                if(is_array($val)) {
                    foreach($val as $kkey=>$vval) {
                        $input[$key.".".$kkey]=$vval;
                    }
                    unset($input[$key]);
                }
            }

            // Create proper entries in DB and ASTDB
            $this->create_param ($f3, $input, 'INSERT');
            $this->create_user  ($f3, $input, 'INSERT');

            // get back to real field names
            $field_map_reverse = array_flip($this->field_map);
            foreach($input as $key=>$val) {
                if(array_key_exists($key,$field_map_reverse)) {
                    unset($input[$key]);
                    $input[$field_map_reverse[$key]]=$val;
                 }
            }

            $this->applyChanges($input);

            // Return new entity in Location header
            $loc    = $f3->get('REALM');
            header("Location: $loc", true, 201);
            die();

        } else {

            // Exising user with specified extension/id, this is an UPDATE

            // Populate variable with existing values from entry in users table
            // and override stored values with passed ones
            $this->data->copyTo('currentvalues');

            $posted_dial='';
            if(isset($input['dial'])) {
                $posted_dial=$input['dial'];
            }

            foreach($f3->get('currentvalues') as $key=>$val) {
                $input[$key] = isset($input[$key])?$input[$key]:$f3->get('currentvalues')[$key];
            }

            $input['extension'] = $f3->get('PARAMS.id');

            $query          = "SELECT tech,dial,emergency_cid FROM devices WHERE id=:id";
            $rews           = $db->exec($query,array(':id'=>$input['extension']));
            $input['tech']  = $rews[0]['tech']; // we cannot modify tech on update
            $input['dial']  = $rews[0]['dial'];
            if($input['tech']=='custom') {
                $input['dial']=($posted_dial<>'')?$posted_dial:$rews[0]['dial'];
            }

            // flatten
            foreach($input as $key=>$val) {
                if(is_array($val)) {
                    foreach($val as $kkey=>$vval) {
                        $input[$key.".".$kkey]=$vval;
                    }
                    unset($input[$key]);
                }
            }

            //validate
            $input = $this->transform_values($f3,$input);
            $input = $this->validate_values($f3,$input);

            $this->create_param ($f3, $input, 'UPDATE');
            $this->create_user  ($f3, $input, 'UPDATE');


            // get back to real field names
            $field_map_reverse = array_flip($this->field_map);
            foreach($input as $key=>$val) {
                if(array_key_exists($key,$field_map_reverse)) {
                    $input[$field_map_reverse[$key]]=$val;
                 }
            }

            $f3->set('INPUT',$input);
            try {
                $this->data->copyFrom('INPUT');
                $this->data->update();
                $this->applyChanges($input);
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
            }

        }
    }

    public function post($f3, $from_child=0) {

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

        if(!isset($input['tech'])) {
            $input['tech']='sip';
        }

        if(!in_array($input['tech'],$this->validations['tech'])) { 
            // default to sip instead of givin 422 error
            $input['tech']='sip'; 
        }

        $TECH = strtolower($input['tech']);

        if(isset($input['extension'])) {
            $EXTEN = $input['extension'];
        } else {
            // Get next extension number from the users table, including gap extensions
            $query = "SELECT cast(extension AS unsigned)+1 AS extension FROM users mo WHERE NOT EXISTS ";
            $query.= "(SELECT NULL FROM users mi  WHERE  cast(mi.extension AS unsigned) = CAST(mo.extension AS unsigned)+ 1) ";
            $query.= "ORDER BY CAST(extension AS unsigned) LIMIT 1";
            $rows  = $db->exec($query);
            $EXTEN = $rows[0]['extension'];
            $input['extension'] = $EXTEN;
        }

        // Check if extension number is valid and it has no collitions
        $this->checkValidExtension($f3,$EXTEN);

        // Default name if not supplied in data
        if(!isset($input['name'])) {
            $input['name'] = 'Extension';
        }

        // Remove any stray DB entries in devices and sip tables
        if($TECH<>'virtual') {
            $db->exec("DELETE FROM devices WHERE id = ?", array(1=>$EXTEN));
        }

        $techtable = $TECH;
        if($techtable=='iax2') { $techtable='iax'; }

        if($techtable<>'virtual' && $techtable<>'custom') {
            $db->exec("DELETE FROM $techtable WHERE id = ?", array(1=>$EXTEN));
        }

        // flatten
        foreach($input as $key=>$val) {
            if(is_array($val)) {
                foreach($val as $kkey=>$vval) {
                    $input[$key.".".$kkey]=$vval;
                }
                unset($input[$key]);
            }
        }

        // validate
        $input = $this->transform_values($f3,$input);
        $input = $this->validate_values($f3,$input);

        // Create proper entries in DB and ASTDB
        $this->create_param ($f3, $input, 'INSERT');
        $this->create_user  ($f3, $input, 'INSERT');

        // get back to real field names
        $field_map_reverse = array_flip($this->field_map);
        foreach($input as $key=>$val) {
            if(array_key_exists($key,$field_map_reverse)) {
                unset($input[$key]);
                $input[$field_map_reverse[$key]]=$val;
             }
        }

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
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400);
            die();
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            $this->data->load(array($this->id_field.'=?',$oneid));

            $query = "SELECT tech FROM devices WHERE id=:id";
            $rews  = $db->exec($query,array(':id'=>$oneid));
            $tech  = $rews[0]['tech'];

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
            if($tech<>'custom' && $tech<>'') {   // virtual extension do not have a devices entry so tech is empty, custom does not have a device table either
                try {
                    $db->exec("DELETE FROM $tech WHERE id=?",array(1=>$oneid));
                } catch(\PDOException $e) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                    die();
                }
            }

            // Delete from devices table using SQL
            if($tech<>'') {
                try {
                    $db->exec("DELETE FROM devices WHERE id=?",array(1=>$oneid));
                } catch(\PDOException $e) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                    die();
                }
            }

            // Delete all relevant ASTDB entries
            $this->ami->DatabaseDelTree('AMPUSER/'.$oneid);
            $this->ami->DatabaseDelTree('CALLTRACE/'.$oneid);
            $this->ami->DatabaseDelTree('CW/'.$oneid);
            $this->ami->DatabaseDelTree('DEVICE/'.$oneid);
        }

        $this->applyChanges($input);
    }

    public function search($f3) {

        $db  = $f3->get('DB');;

        if($f3->get('PARAMS.term')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        // sip
        $query=  "SELECT a.extension,b.description AS name,b.tech,b.dial,c.data AS secret,d.data AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN sip c ON a.extension=c.id LEFT JOIN sip d ON a.extension=d.id WHERE c.keyword='secret' AND d.keyword='context' AND (b.description LIKE ? AND a.extension REGEXP ?) ";
        $query.= "UNION ";
        // iax
        $query.=  "SELECT a.extension,b.description AS name,b.tech,b.dial,c.data AS secret,d.data AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN iax c ON a.extension=c.id LEFT JOIN iax d ON a.extension=d.id WHERE c.keyword='secret' AND d.keyword='context' AND (b.description LIKE ? AND a.extension REGEXP ?) ";
        $query.= "UNION ";
        // dahdi 
        $query.=  "SELECT a.extension,b.description AS name,b.tech,b.dial,'' AS secret,c.data AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id LEFT JOIN dahdi c ON a.extension=c.id WHERE c.keyword='context' AND (b.description LIKE ? AND a.extension REGEXP ?) ";
        $query.= "UNION ";
        // custom
        $query.=  "SELECT a.extension,b.description AS name,b.tech,b.dial,'' AS secret,'' AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id WHERE tech='custom' AND (b.description LIKE ? AND a.extension REGEXP ?) ";
        $query.= "UNION ";
        // virtual
        $query.=  "SELECT a.extension,b.description AS name,'virtual' AS tech,'' AS  dial,'' AS secret,'' AS context,IFNULL(emergency_cid,'') AS emergency_cid,outboundcid,sipname,";
        $query.= "noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest FROM users a ";
        $query.= "LEFT JOIN devices b ON a.extension = b.id WHERE tech is NULL AND (b.description LIKE ? AND a.extension REGEXP ?) ";

        $query.= "ORDER BY 1";

        $search = strtolower($f3->get('PARAMS.term'));
        
        if(preg_match('/^[0-9xzn\.\^]*$/',$search)) {
            $letters = str_split($search);
            $searchfinal='';
            foreach($letters as $char) {
                $add = $char;
                if($char == 'x') {
                    $add='[0-9]';
                } else if($char == 'z') {
                    $add='[1-9]';
                } else if($char == 'n') {
                    $add='[2-9]';
                } else if($char == '.') {
                    $add='.*';
                }
                $searchfinal.=$add;
            }     
            $rows  = $db->exec($query,array( "%%", $searchfinal, "%%", $searchfinal, "%%", $searchfinal,  "%%", $searchfinal,  "%%", $searchfinal));
        } else {
            $rows  = $db->exec($query,array( "%$search%", '.', "%$search%", '.', "%$search%", '.',  "%$search%", '.',  "%$search%", '.'));
        }


        // check if we have fields specified
        parse_str($f3->QUERY, $qparams);
        if(isset($qparams['fields'])) { $extrafields = $qparams['fields']; } else { $extrafields=''; }

        $result = array();

        foreach($rows as $idx=>$data) {

            $allextrafields = explode(',',$extrafields);

            $tech = $data['tech'];
            if($tech=='iax2') { $tech='iax'; }

            $final = array();

            if($tech<>'custom' && $tech<>'virtual') {  // custom extension do not have a table to get extra data from
                $query = "SELECT keyword,data FROM ".$tech." WHERE id=:id AND keyword NOT IN ('secret','context','dial') ORDER BY flags";
                $rews = $db->exec($query,array(':id'=>$data['extension']));

                foreach($rews as $ii=>$dd) { 
                    if(array_key_exists('parameters.'.$dd['keyword'],$this->field_map)) {
                        $index = $this->field_map['parameters.'.$dd['keyword']];
                    } else {
                        $index = 'device_options.'.$dd['keyword'];
                    }
                    $final[$index]=$dd['data']; 
                }
            }


            $final['extension_options.pinless_dialing']='';  // if disabled it has no entry, so asume it empty
            $final['extension_options.call_waiting']='';  // if disabled it has no entry, so asume it empty

            $res = $this->ami->DatabaseShow('AMPUSER/'.$data['extension']);
            foreach($res as $key=>$val) {
                $partes = preg_split("/\//",$key);
                array_shift($partes);
                array_shift($partes);
                array_shift($partes);
                $astdbkey = implode("/",$partes);
                if($astdbkey=='outboundcid') { continue; } // we already have this in the users table
                if($astdbkey=='device')      { continue; } // we wont use user and device mode in api
                if($astdbkey=='noanswer')    { continue; } // not used anymore? 

                if(array_key_exists('astdb.ampusers.'.$astdbkey, $this->field_map)) {
                    $reskey = $this->field_map['astdb.ampusers.'.$astdbkey];
                    $final[$reskey]=$val;
                } 
            }

            $res = $this->ami->DatabaseShow('CW/'.$data['extension']);
            foreach($res as $key=>$val) { 
                $final[$this->field_map['astdb.cw']]=$val; 
            }

            // field map on users table
            foreach($rows[$idx] as $key=>$val) {
                if(array_key_exists($key,$this->field_map)) {
                    unset($rows[$idx][$key]);
                    $rows[$idx][$this->field_map[$key]]=$val;
                }
            }

            $complete_array = array_merge($rows[$idx],$final); 

            $complete_array = $this->presentation_transform_values($f3,$complete_array);

            // apply dicitionary/map on field names (unflatten)
            foreach($complete_array as $key=>$val) {
                $partes = explode(".",$key);
                if(count($partes)>1) {
                    unset($complete_array[$key]);
                    if(!isset($complete_array[$partes[0]])) { $complete_array[$partes[0]]=array(); }
                    $complete_array[$partes[0]][$partes[1]]=$val;
                }
            }

            $print_fields = array_merge($this->list_fields,$allextrafields);

            // remove not asked fields from collection results
            if($extrafields<>'*') {
                // final has both tech table + astdb entries, if collection filter out unless field requested
                foreach($complete_array as $key=>$val) {
                    if(is_array($val)) {
                        foreach($val as $subkey=>$subval) {
                            if(!in_array($key.".".$subkey,$print_fields)) {
                                unset($complete_array[$key][$subkey]);
                            } 
                        } 

                    } else {
                        if(!in_array($key,$print_fields)) {
                            unset($complete_array[$key]);
                        } 
                    }
                }
                if(count($complete_array['device_options'])==0) {
                    unset($complete_array['device_options']);
                }
                if(count($complete_array['extension_options'])==0) {
                    unset($complete_array['extension_options']);
                }
                if(count($complete_array['recording'])==0) {
                    unset($complete_array['recording']);
                }
                if(count($complete_array['callerid_override'])==0) {
                    unset($complete_array['callerid_override']);
                }
                if(count($complete_array['destination'])==0) {
                    unset($complete_array['destination']);
                }
            }

            $result[]=$complete_array;
        }

        // for security reasons we wrap results array into one object
        // https://www.owasp.org/index.php/AJAX_Security_Cheat_Sheet#Always_return_JSON_with_an_Object_on_the_outside

        $final = array();
        $final['results'] = $result;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();

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

        if(in_array($extension,$this->allextensions)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }

        return true;

    }
   
    // transformation functions 
    protected function presentationTransformPinless($data) {
        if($data=='NOPASSWD') { return 'yes'; } else { return 'no'; }
    }
    protected function transformPinless($data) {
        if($data=='yes') { return 'NOPASSWD'; } else { return ''; }
    }

    protected function presentationTransformCallWaiting($data) {
        if($data=='ENABLED') { return 'yes'; } else { return 'no'; }
    }
    protected function transformCallWaiting($data) {
        if($data=='yes') { return 'ENABLED'; } else { return ''; }
    }

    protected function presentationTransformAutoAnswer($data) {
        if($data=='intercom') { return 'yes'; } else { return 'no'; }
    }
    protected function transformAutoAnswer($data) {
        if($data=='yes') { return 'intercom'; } else { return 'disabled'; }
    }

    protected function presentationTransformQueueState($data) {
        if($data=='usestate') { return 'yes'; } else { return 'no'; }
    }
    protected function transformQueueState($data) {
        if($data=='yes') { return 'usestate'; } else { return 'ignorestate'; }
    }

    protected function presentationTransformOndemand($data) {
        if($data=='enabled') { return 'yes'; } else { return 'no'; }
    }
    protected function transformOndemand($data) {
        if($data=='yes') { return 'enabled'; } else { return 'disabled'; }
    }

    // validation functions
    protected function check_digit($data) {
        if(!preg_match("/^([0-9]*)$/",$data)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }
        return $data;
    }

    protected function check_transport($data) {
        $valid_transports = array('wss','ws','udp','tcp','tls');
        $input_transports = explode(',',$data);
        $result = array_filter($input_transports, function($v) use ($valid_transports) { if(in_array($v,$valid_transports)) { return false; } else { return true; }});
        if(count($result)>0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }
        return $data;
    }

    protected function check_acl($data) {
        $parts = preg_split("/\//",$data,2);
        $ok=1;
        foreach($parts as $element) {
            if(!preg_match('/^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',$element,$matches)) {
                $ok=0;
            }
        }
        if($ok==0) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 422 Unprocessable Entity', true, 422);
            die();
        }
        return $data;
    }

}


