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
  $Id: vmblast.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class vmblast extends rest {

    protected $table           = "vmblast";
    protected $id_field        = 'grpnum';
    protected $name_field      = 'description';
    protected $extension_field = 'grpnum';
    protected $list_fields     = array('audio_label','password');
    protected $search_field    = 'description';
    protected $mailboxes       = array();
    protected $recordings      = array();

    protected $provides_destinations = true;
    protected $context               = 'vmblast-grp';
    protected $category              = 'Voicemail Blasting';

    protected $field_map = array(
        'audio_label' => 'announcement_id'   // -1 = read group number,  -2 = beep only
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {

        parent::__construct($f3,0,1);

        $mbx = new mailboxes($f3);
        $mbx->setGetAll(1);
        $res  = $mbx->get($f3,1);
        $mbx->setGetAll(0);
        foreach($res as $idx=>$data) {
            $this->mailboxes[]=$data['extension'];
        }

        $recs = new recordings($f3);
        $recs->setGetAll(1);
        $res  = $recs->get($f3,1);
        $recs->setGetAll(0);

        foreach($res as $idx=>$data) {
            $files=array();
            foreach($data['audiofiles'] as $idx2=>$data2) {
                $files[] = $data2['filename'];
            }
            $this->recordings[implode("&",$files)]=$data['id'];
        }
        $this->recordings['beep']=-2;
        $this->recordings['read']=-1;
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {

            $mailbox_list = array();
            $rews = $db->exec("SELECT ext FROM vmblast_groups WHERE grpnum=?", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $mailbox_list[]=$data2['ext'];
            }
            $rows[$idx]['mailbox_list']=$mailbox_list;
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    public function put($f3,$from_child) {

        $db  = $f3->get('DB');

        $input = $this->parseInputData($f3);

        $rec = array_flip($this->recordings);

        unset($input['extension']);

        // die if any passed member is not a valid extension
        if(isset($input['mailbox_list'])) {
            if(is_array($input['mailbox_list'])) {
                foreach($input['mailbox_list'] as $member) {
                    if(!in_array($member,$this->mailboxes)) {
                        $errors[]=array('status'=>'422','source'=>'mailbox_list', 'detail'=>$member.' is not a valid mailbox');
                    }
                }
            }
        }

        if(isset($input['announcement_id'])) {
            if(!array_key_exists($input['announcement_id'],$rec)) {
                $errors[]=array('status'=>'422','source'=>'announcement_id', 'detail'=>'Invalid announcement id');
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        // update main table
        parent::put($f3,1);

        $vmblastid = $f3->get('PARAMS.id');

        // insert in member groups table 
        $query = "DELETE FROM vmblast_groups WHERE grpnum=?";
        $db->exec($query,$vmblastid);
        if(isset($input['mailbox_list'])) {
            if(is_array($input['mailbox_list'])) {
                foreach($input['mailbox_list'] as $member) {
                    $db->exec("INSERT INTO vmblast_groups (grpnum,ext) VALUES (?,?)",array($vmblastid,$member));
                }
            }
        }

        $this->applyChanges($input);

    }

    public function post($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');
        $errors = array();

        $input = $this->parseInputData($f3);

        $rec = array_flip($this->recordings);

        $this->dieExtensionDuplicate($f3,$input['extension']);

        // die if any passed member is not a valid extension
        if(isset($input['mailbox_list'])) {
            if(is_array($input['mailbox_list'])) {
                foreach($input['mailbox_list'] as $member) {
                    if(!in_array($member,$this->mailboxes)) {
                        $errors[]=array('status'=>'422','source'=>'mailbox_list', 'detail'=>$member.' is not a valid mailbox');
                    }
                }
            }
        }

        if(isset($input['announcement_id'])) {
            if(!array_key_exists($input['announcement_id'],$rec)) {
                $errors[]=array('status'=>'422','source'=>'announcement_id', 'detail'=>'Invalid announcement id');
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        // insert in main table 
        $vmblastid = parent::post($f3,1);

        // insert in member groups table 
        $query = "DELETE FROM vmblast_groups WHERE grpnum=?";
        $db->exec($query,$vmblastid);
        if(isset($input['mailbox_list'])) {
            if(is_array($input['mailbox_list'])) {
                foreach($input['mailbox_list'] as $member) {
                    $db->exec("INSERT INTO vmblast_groups (grpnum,ext) VALUES (?,?)",array($vmblastid,$member));
                }
            }
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$vmblastid, true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            $query = "DELETE FROM vmblast_groups WHERE grpnum=?";
            $db->exec($query,$oneid);
        }

        $this->applyChanges($input);

    }

}
