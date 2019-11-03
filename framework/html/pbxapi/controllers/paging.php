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
  $Id: paging.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class paging extends rest {

    protected $table           = "paging_config";
    protected $id_field        = 'page_group';
    protected $name_field      = 'description';
    protected $extension_field = 'page_group';
    protected $list_fields     = array('force_page','duplex');
    protected $search_field    = 'description';
    protected $allextensions   = array();
    protected $recordings      = array();

    protected $provides_destinations = true;
    protected $context               = 'app-pagegroups';
    protected $category              = 'Paging and Intercom';

    protected $field_map = array(
        'force_page' => 'busy_extensions',
    );

    protected $transforms = array( 
        'busy_extensions' => 'forcepage',
        'duplex'          => 'enabled',
    );

    protected $presentationTransforms = array( 
        'busy_extensions' => 'presentation_forcepage',
        'duplex'          => 'presentation_enabled',
    );

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,0,1);

        $alldest = new extensions($f3);
        $this->allextensions = $alldest->getExtensions($f3);

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

        $this->recordings['beep']=0;
        $this->recordings['']=-1;
    }

    public function get($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');

        $rows = parent::get($f3,1);

        $rews = $db->exec("SELECT * FROM paging_autoanswer WHERE useragent='default' AND var='DOPTIONS'");
        $announce = substr($rews[0]['setting'],2,-1);
        $recording_id = $this->recordings[$announce];

        foreach($rows as $idx=>$data) {

            $extension_list = array();
            $rews = $db->exec("SELECT ext FROM paging_groups WHERE page_number=?", array($data['id']));
            foreach($rews as $idx2=>$data2) {
                $extension_list[]=$data2['ext'];
            }
            $rows[$idx]['extension_list']=$extension_list;
            $rows[$idx]['global_announcement_id']=$recording_id;
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

        unset($input['extension']);

        // die if any passed member is not a valid extension
        if(isset($input['extension_list'])) {
            if(is_array($input['extension_list'])) {
                foreach($input['extension_list'] as $member) {
                    if(!in_array($member,$this->allextensions)) {
                        $errors[]=array('status'=>'422','source'=>'extension_list', 'detail'=>$member.' is not a valid extension');
                    }
                }
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        // update main table
        parent::put($f3,1);

        $pagingid = $f3->get('PARAMS.id');

        // insert in member groups table 
        if(isset($input['extension_list'])) {
            if(is_array($input['extension_list'])) {
                $query = "DELETE FROM paging_groups WHERE page_number=?";
                $db->exec($query,$pagingid);
                foreach($input['extension_list'] as $member) {
                    $db->exec("INSERT INTO paging_groups (page_number,ext) VALUES (?,?)",array($pagingid,$member));
                }
            }
        }

        if(isset($input['global_announcement_id'])) {
            $recs = array_flip($this->recordings);
            $audios = $recs[$input['global_announcement_id']];
            $db->exec("DELETE FROM paging_autoanswer WHERE useragent='default' AND var='DOPTIONS'");
            $db->exec("INSERT INTO paging_autoanswer (useragent,var,setting) VALUES (?,?,?)",array('default','DOPTIONS','A('.$audios.')'));
        }

        $this->applyChanges($input);

    }

    public function post($f3, $from_child=0) {

        $db  = $f3->get('DB');
        $ami = $f3->get('AMI');
        $errors = array();

        $input = $this->parseInputData($f3);

        $this->dieExtensionDuplicate($f3,$input['extension']);

        // die if any passed member is not a valid extension
        if(isset($input['extension_list'])) {
            if(is_array($input['extension_list'])) {
                foreach($input['extension_list'] as $member) {
                    if(!in_array($member,$this->allextensions)) {
                        $errors[]=array('status'=>'422','source'=>'extension_list', 'detail'=>$member.' is not a valid extension');
                    }
                }
            }
        }

        if(count($errors)>0) {
            $this->dieWithErrors($errors);
        }

        // insert in main table 
        $pagingid = parent::post($f3,1);

        // insert in member groups table 
        if(isset($input['extension_list'])) {
            if(is_array($input['extension_list'])) {
                $query = "DELETE FROM paging_groups WHERE page_number=?";
                $db->exec($query,$pagingid);
                foreach($input['extension_list'] as $member) {
                    $db->exec("INSERT INTO paging_groups (page_number,ext) VALUES (?,?)",array($pagingid,$member));
                }
            }
        }

        if(isset($input['global_announcement_id'])) {
            $recs = array_flip($this->recordings);
            $audios = $recs[$input['global_announcement_id']];
            $db->exec("DELETE FROM paging_autoanswer WHERE useragent='default' AND var='DOPTIONS'");
            $db->exec("INSERT INTO paging_autoanswer (useragent,var,setting) VALUES (?,?,?)",array('default','DOPTIONS','A('.$audios.')'));
        }

        $this->applyChanges($input);

        // Return new entity in Location header
        $loc = $f3->get('REALM');
        header("Location: $loc/".$pagingid, true, 201);
        die();

    }

    public function delete($f3,$from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            $query = "DELETE FROM paging_groups WHERE page_number=?";
            $db->exec($query,$oneid);
        }

        $this->applyChanges($input);

    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }

    public function forcepage($data) {
        $values = array( 'skip'=>'0', 'force'=>'1', 'whisper'=>'2' );
        $ret = isset($values[$data])?$values[$data]:'0';
        return $ret;
    }

    public function presentation_forcepage($data) {
        $values = array( '0'=>'skip', '1'=>'force', '2'=>'whisper' );
        $ret = isset($values[$data])?$values[$data]:'0';
        return $ret;
    }

}
