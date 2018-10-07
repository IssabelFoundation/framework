<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
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
  $Id: trunks.php, Sat 06 Oct 2018 10:19:05 PM EDT, nicolas@issabel.com
*/

class trunks extends rest {
    protected $table      = "trunks";
    protected $id_field   = 'trunkid';
    protected $name_field = 'name';
    protected $dest_field = "";
    protected $list_fields = array('tech','channelid');
    protected $extension_field = '';
    protected $ami;
    protected $conn;

    // callerid_options   (keepcid)
    // off   = Allow Any CID
    // on    = Block foreign CID
    // cnum  = Remove CNAM
    // all   = Force Trunk CID

    protected $field_map = array(
        'tech'               => 'technology',
        'channelid'          => 'channel_name',
        'usercontext'        => 'user_context',
        'maxchans'           => 'maximum_channels',
        'outcid'             => 'outbound_callerid',
        'keepcid'            => 'callerid_options',
        'dialoutprefix'      => 'dialout_prefix',
        'continue'           => 'continue_if_busy',
    );

    function __construct($f3) {

        $mgrpass     = $f3->get('MGRPASS');
        $this->ami   = new asteriskmanager();
        $this->conn  = $this->ami->connect("localhost","admin",$mgrpass);

        if(!$this->conn) {
           header($_SERVER['SERVER_PROTOCOL'] . ' 502 Service Unavailable', true, 502);
           die();
        }

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

        $original_results= parent::get($f3,1);


        // get ASTDB trunk dial options
        $res = $this->ami->DatabaseShow('TRUNK');
        $astdb = array();
        foreach($res as $key=>$val) {
            $partes = preg_split("/\//",$key);
            $astdb[$partes[3]][$partes[2]]=$val;
        }

        foreach($original_results as $idx=>$data) {
            // get sip trunk details
            $trunkid = $data['id'];
            if(isset($astdb['dialopts'][$trunkid])) {
                $original_results[$idx]['dial_options']=$astdb['dialopts'][$trunkid];
            }

            //get dial patters
            $query = "SELECT * FROM trunk_dialpatterns WHERE trunkid=?";
            $rows = $db->exec($query,array($trunkid));
            foreach($rows as $idx2=>$datapattern) {
                foreach($datapattern as $key=>$val) {
                    $finalkey = isset($this->field_map[$key])?$this->field_map[$key]:$key;
                    unset($rows[$idx2][$key]);
                    $rows[$idx2][$finalkey]=$val;
                }
                unset($rows[$idx2]['trunkid']);
            }
            $original_results[$idx]['patterns']=count($rows)>0?$rows:array();

            if($data['technology']=='sip') {
                $peer = array();
                $user = array();
                $register = '';
                $query = "SELECT * FROM sip WHERE id LIKE 'tr%-$trunkid'";
                $detrows = $this->db->exec($query);
                foreach($detrows as $row) {
                    if(preg_match("/^tr-peer/",$row['id'])) {
                        $peer[$row['keyword']]=$row['data'];
                    } else if (preg_match("/^tr-user/",$row['id'])) {
                        $user[$row['keyword']]=$row['data'];
                    } else if (preg_match("/^tr-reg/",$row['id'])) {
                        $register = $row['data'];
                    }
                }
                $original_results[$idx]['peer']=$peer;
                $original_results[$idx]['user']=$user;
                if($register<>'') { $original_results[$idx]['register']=$register; }
            }

        }

        // final json output
        $final = array();
        $final['results'] = $original_results;
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($final);
        die();
    }

}


