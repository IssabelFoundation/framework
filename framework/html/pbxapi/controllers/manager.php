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
  $Id: manager.php, Tue 04 Sep 2018 09:53:45 AM EDT, nicolas@issabel.com
*/

class manager extends asmanager {

    protected $response = array();

    function delete($f3) {

        if($this->conn) {
            $action = $f3->get('PARAMS.id');
            switch($action) {

            case "dbdel":
                parse_str($f3->get('BODY'),$params);
                $family = $params['family'];
                $key    = $params['key'];
                $res = $this->ami->DatabaseDel($family,$key);
                header('Content-Type: application/json');
                if($res) {
                    echo "{\"status\":\"ok\"}";
                } else {
                    echo "{\"status\":\"error\"}";
                }
                break;

            }
        }
    }

    function post($f3) {

        if($this->conn) {
            $action = $f3->get('PARAMS.id');
            switch($action) {

            case "dbput":
                parse_str($f3->get('BODY'),$params);
                $family = $params['family'];
                $key    = $params['key'];
                $value  = $params['value'];
                $res = $this->ami->DatabasePut($family,$key,$value);
                header('Content-Type: application/json');
                if($res) {
                    echo "{\"status\":\"ok\"}";
                } else {
                    echo "{\"status\":\"error\"}";
                }
                break;

            }
        }
    }

    function get($f3) {

        if($this->conn) {
            $action = $f3->get('PARAMS.id');

            switch($action) {

            case "queuestatus":

                $queue  = $f3->get('REQUEST.queue');

                $this->ami->add_event_handler("QueueStatusComplete", [$this,"myQueueStatus"]);
                $this->ami->add_event_handler("QueueMember",         [$this,"myQueueStatus"]);
                $this->ami->add_event_handler("QueueParams",         [$this,"myQueueStatus"]);
                $this->ami->add_event_handler("QueueEntry",          [$this,"myQueueStatus"]);

                $res = $this->ami->QueueStatus($queue);
                if($res['Response']!='Error') {
                    $this->ami->wait_response(true);
                }
                header('Content-Type: application/json');
                echo json_encode($this->response);
                break;

            case "status":

                $channel = $f3->get('REQUEST.channel');

                $this->ami->add_event_handler("StatusComplete", [$this,"myChannelStatus"]);
                $this->ami->add_event_handler("Status",         [$this,"myChannelStatus"]);
                $res = $this->ami->Status($channel);
                if($res['Response']!='Error') {
                    $this->ami->wait_response(true);
                }
                header('Content-Type: application/json');
                echo json_encode($this->response);
                break;

            case "extensionstate":

                $extension = $f3->get('REQUEST.extension');
                $context   = $f3->get('REQUEST.context');
                $uniqueid  = $f3->get('REQUEST.uniqueid');
                $res = $this->ami->ExtensionState($extension,$context,$uniqueid);
                header('Content-Type: application/json');
                echo json_encode($res);
                break;

            case "dbput":

                $family = $f3->get('REQUEST.family');
                $key    = $f3->get('REQUEST.key');
                $value  = $f3->get('REQUEST.value');
                $res = $this->ami->DatabasePut($family,$key,$value);
                header('Content-Type: application/json');
                if($res) {
                    echo "{\"status\":\"ok\"}";
                } else {
                    echo "{\"status\":\"error\"}";
                }
                break;

            case "dbdel":

                $family = $f3->get('REQUEST.family');
                $key    = $f3->get('REQUEST.key');
                $res = $this->ami->DatabaseDel($family,$key);
                header('Content-Type: application/json');
                if($res) {
                    echo "{\"status\":\"ok\"}";
                } else {
                    echo "{\"status\":\"error\"}";
                }
                break;

            case "dbdeltree":

                $family = $f3->get('REQUEST.family');
                $res = $this->ami->DatabaseDelTree($family);
                header('Content-Type: application/json');
                if($res) {
                    echo "{\"status\":\"ok\"}";
                } else {
                    echo "{\"status\":\"error\"}";
                }
                break;

            case "dbget":

                $family = $f3->get('REQUEST.family');
                $key    = $f3->get('REQUEST.key');
                $res = $this->ami->DatabaseGet($family,$key);
                header('Content-Type: application/json');
                if($res<>'') {
                    echo "{\"result\":\"$res\"}";

                } else {
                    echo "{\"result\":\"\"}";

                }
                break;

            case "dbshow":

                $family = $f3->get('REQUEST.family');
                $res = $this->ami->DatabaseShow($family);

                header('Content-Type: application/json');

                if(count($res)>0) {

                    foreach($res as $key=>$value) {
                        $pos = strpos($family,"/");
                        if($pos>0) {
                            $idx = substr($family,$pos+1);
                            $finalkey = substr($key,strlen($family)+2);
                            $finaldata[$idx][$finalkey]=$value;

                        } else {

                            $key = substr($key,strlen($family)+2);
                            if(strpos($key,"/")>0) {
                                list($idx,$restkey) = preg_split("/\//",$key,2);
                            } else {
                                $idx=0;
                            }
                            $finaldata[$idx][$restkey]=$value;
                        }
                    }
                    echo json_encode($finaldata);

                } else {
                    echo "{}";
                }
                break;

            case "queueadd":

                $queue          = $f3->get('REQUEST.queue');
                $interface      = $f3->get('REQUEST.interface');
                $penalty        = $f3->get('REQUEST.penalty');
                $paused         = $f3->get('REQUEST.paused');
                $membername     = $f3->get('REQUEST.membername');
                $stateinterface = $f3->get('REQUEST.stateinterface');

                $res = $this->ami->QueueAdd($queue,$interface,$penalty,$paused,$membername,$stateinterface);

                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }

                break;

            case "queueremove":

                $queue          = $f3->get('REQUEST.queue');
                $interface      = $f3->get('REQUEST.interface');

                $res = $this->ami->QueueRemove($queue,$interface);

                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "queuepause":

                $queue     = $f3->get('REQUEST.queue');
                $interface = $f3->get('REQUEST.interface');

                $res = $this->ami->QueuePause($queue,$interface);

                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "queueunpause":

                $queue     = $f3->get('REQUEST.queue');
                $interface = $f3->get('REQUEST.interface');

                $res = $this->ami->QueueUnpause($queue,$interface);

                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "queuelog":

                $params = array();
                $params['Queue']     = $f3->get('REQUEST.queue');
                $params['Event']     = $f3->get('REQUEST.event');
                $params['Uniqueid']  = $f3->get('REQUEST.uniqueid');
                $params['Interface'] = $f3->get('REQUEST.interface');
                $params['Message']   = $f3->get('REQUEST.message');
                $res = $this->ami->QueueLog($params);
                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "userevent":

                $event  = $f3->get('REQUEST.event');
                $params = array();
                foreach($f3->get('REQUEST') as $key=>$val) {
                    if($key<>'event') {
                        $params[$key]=$val;
                    }
                }
                header('Content-Type: application/json');
                if($event<>'') {
                    $res = $this->ami->UserEvent($event,$params);
                    if($res['Response']=='Success') {
                        echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                    } else {
                        echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                    }
                } else {
                    echo "{\"status\":\"error\", \"message\":\"No event header specified\"}";
                }
                break;

            case "reload":

                $res = $this->ami->Reload();
                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "hangup":

                $channel = $f3->get('REQUEST.channel');
                $cause   = $f3->get('REQUEST.cause');

                $res = $this->ami->Hangup($channel,$cause);

                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "getvar":

                $channel  = $f3->get('REQUEST.channel');
                $variable = $f3->get('REQUEST.variable');

                $res = $this->ami->GetVar($channel,$variable);
                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    $var = $res['Variable'];
                    $val = $res['Value'];
                    echo "{\"status\":\"ok\", \"variable\":\"$var\",\"value\":\"$val\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            case "originate":

                $channel     = $f3->get('REQUEST.channel');
                $extension   = $f3->get('REQUEST.extension');
                $context     = $f3->get('REQUEST.context');
                $priority    = $f3->get('REQUEST.priority');
                $timeout     = $f3->get('REQUEST.timeout');
                $callerid    = $f3->get('REQUEST.callerid');
                $variable    = $f3->get('REQUEST.variable');
                $account     = $f3->get('REQUEST.account');
                $application = $f3->get('REQUEST.application');
                $data        = $f3->get('REQUEST.data');

                $res = $this->ami->Originate($channel,$extension,$context,$priority,$timeout,$callerid,$variable,$account,$application,$data);

                header('Content-Type: application/json');
                if($res['Response']=='Success') {
                    echo "{\"status\":\"ok\", \"message\":\"".$res['Message']."\"}";
                } else {
                    echo "{\"status\":\"error\", \"message\":\"".$res['Message']."\"}";
                }
                break;

            default:
                $this->print_instructions();
                break;

           }
        }
    }

    function myChannelStatus($event,$data) {
        if($event=='statuscomplete') {
            $this->ami->disconnect();
        } else {
            array_push($this->response,$data);
        }
    }

    function myQueueStatus($event,$data) {
        if($event=='queuestatuscomplete') {
            $this->ami->disconnect();
        } else {
            array_push($this->response,$data);
        }
    }

    function print_instructions() {
        $commands= array();
        $commands['queuestatus']    = Array('[queue]');
        $commands['status']         = Array('[channel]');
        $commands['extensionstate'] = Array('extension','[context]','[uniqueid]');
        $commands['dbget']          = Array('family','key');
        $commands['dbput']          = Array('family','key','value');
        $commands['dbdel']          = Array('family','key');
        $commands['dbshow']         = Array('family');
        $commands['queueadd']       = Array('queue','interface','[penalty]','[paused]','[membername]','[stateinterface]');
        $commands['queueremove']    = Array('queue','interface');
        $commands['queuepause']     = Array('queue','interface');
        $commands['queueunpause']   = Array('queue','interface');
        $commands['queuelog']       = Array('queue','event','[uniqueid]','[interface]','[message]');
        $commands['reload']         = Array();
        $commands['getvar']         = Array('channel','variable');
        $commands['hangup']         = Array('channel','[cause]');
        $commands['userevent']      = Array('event','[@headers]');
        $commands['originate']      = Array('channel','extension','context','priority','[timeout]','[callerid]','[variable]','[account]','[application]','[data]');
        ksort($commands);
        header('Content-Type: application/json');
        echo json_encode($commands);
    }

}

