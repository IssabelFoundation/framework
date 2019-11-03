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
  $Id: recordings.php, Tue 04 Sep 2018 09:53:01 AM EDT, nicolas@issabel.com
*/

class recordings extends rest {
    protected $table      = "recordings";
    protected $id_field   = 'id';
    protected $name_field = 'displayname';
    protected $extension_field = '';
    protected $list_fields = array('filename');

    protected $field_map = array(
        'fcode'      => 'feature_code_enabled',
        'fcode_pass' => 'feature_code_password'
    );

    protected $presentationTransforms = array(
        'feature_code_enabled'     => 'presentation_enabled',
    );

    protected $transforms = array(
        'feature_code_enabled'     => 'enabled',
    );

    public function get($f3, $from_child=0) {

        $db = $f3->get('DB');

        $rows = parent::get($f3,1);

        foreach($rows as $idx=>$data) {
            if($data['filename']<>'') {
                $files = array();
                $partes = preg_split("/&/",$data['filename']);
                $i=0;
                foreach($partes as $file) {
                    $files[]=array('filename'=>$file,'sequence'=>$i);
                    $i++;
                }
                unset($rows[$idx]['filename']);
                $rows[$idx]['audiofiles']=$files;
            }
        }

        if(is_array($from_child)) {
            $this->outputSuccess($rows);
        } else {
            return $rows;
        }
    }

    function put($f3,$from_child) {
        // UPDATE Record
        $errors = array();
        $db = $f3->get('DB');

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $this->data->load(array($this->id_field.'=?',$f3->get('PARAMS.id')));

        if ($this->data->dry()) {
            $errors[]=array('status'=>'404','detail'=>'Could not find a record to update');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);

        // process audio files array and flatten into filename string concatenated with & symbol
        if(isset($input['audiofiles'])) {
            if(!is_array($input['audiofiles'])) {
                $errors[]=array('status'=>'422','source'=>'audiofiles','detail'=>'Incorrect format');
                $this->dieWithErrors($errors);
            }
            $files = array();
            foreach($input['audiofiles'] as $idx=>$data) {
                $files[$data['sequence']]=$data['filename'];
            }
            ksort($files);
            unset($input['audiofiles']);
            $input['filename']=implode('&',$files);
        }

        // insert or remove feature code
        if(isset($input['feature_code_enabled'])) {
            $recid = $f3->get('PARAMS.id');
            $name = isset($input['name'])?$input['name']:$this->data->name;
            if($input['feature_code_enabled']=='yes') {
                $query = "DELETE FROM featurecodes WHERE modulename='recordings' AND featurename=?";
                $db->exec($query,array('edit-recording-'.$recid));
                $query = "INSERT INTO featurecodes (modulename,featurename,description,defaultcode,enabled,providedest) VALUES (?,?,?,?,?,?)";
                $db->exec($query,array('recordings','edit-recording-'.$recid,'Edit Recording: '.$name,'*29'.$recid,1,1));
            } else {
                $query = "DELETE FROM featurecodes WHERE modulename='recordings' AND featurename=?";
                $db->exec($query,array('edit-recording-'.$recid));
            }
        }

        $input = $this->flatten($input);
        $input = $this->transformValues($f3,$input);
        $input = $this->validateValues($f3,$input);

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
            $this->data->update();
        } catch(\PDOException $e) {
            $msg  = $e->getMessage();
            $code = $e->getCode();
            $errors[]=array('status'=>'400','detail'=>$msg, 'code'=>$code);
            $this->dieWithErrors($errors);
        }

        $this->applyChanges($input);
    }

    public function post($f3,$from_child) {

        $loc = $f3->get('REALM');

        if($f3->get('PARAMS.id')<>'') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $input = $f3->get('POST');

        $files = $f3->get('FILES');

        // post is special as it will accept a file to be uploaded
        if(count($files)>0) {
            $recid = $this->handleUpload($f3);
            if($recid>0) {
                header("Location: $loc/$recid", true, 201);
            } else {
                $errors[]=array('status'=>'500','detail'=>'Could not process uploaded file');
                $this->dieWithErrors($errors);
            }
        } else {
            $recid = parent::post($f3,1);
            header("Location: $loc/$recid", true, 201);
        }
        die();

    }

    public function delete($f3,$from_child) {

        $db = $f3->get('DB');

        parent::delete($f3,1);

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {
            $query = "DELETE FROM featurecodes WHERE modulename='recordings' AND featurename=?";
            $db->exec($query,array('edit-recording-'.$oneid));
        }

        $this->applyChanges($input);

    }

    private function handleUpload($f3) {
        // audio recording upload from web control
        $errors = array();
        $db     = $f3->get('DB');
        $post   = $f3->get('POST');
        $FILES  = $f3->get('FILES');

        if(isset($FILES['file'])) {
            if($FILES['error']==0) {

                $query = "INSERT INTO recordings (displayname,filename) VALUES (?,?)";
                $db->exec($query,array('','custom/new'));
                $insertid = $db->lastInsertId();

                if(!is_dir('/var/lib/asterisk/sounds/custom')) {
                    mkdir('/var/lib/asterisk/sounds/custom');
                }
                $input          = $FILES['file']['tmp_name'];
                $actualfilename = $FILES['file']['name'];
                $ext            = pathinfo($actualfilename, PATHINFO_EXTENSION);
                $basefname      = pathinfo($actualfilename, PATHINFO_FILENAME);
                $fname          = isset($post['filename'])?$post['filename']:$FILES['file']['name'];

                // only allow letters/digits and dot on filenames
                $fname = preg_replace("/[^a-zA-Z0-9\.]+/", "", $fname);
                $basefname = preg_replace("/[^a-zA-Z0-9\.]+/", "", $basefname);

                if($fname=='00000000.wav') {
                    // use numbered filenames using last insert id
                    $fname = sprintf('%08d', $insertid).".wav"; 
                    $basefname = sprintf('%08d', $insertid);
                }

                $output = "/var/lib/asterisk/sounds/custom/".$fname;
                if($ext<>'wav') {
                    $errors[]=array('status'=>'422','detail'=>'Only wav files supported');
                    $this->dieWithErrors($errors);
                }

                if(move_uploaded_file($input, $output)) {;
                    $convertido = preg_replace("/\.wav/","-downsample.wav",$output);
                    exec("sox $output -c 1 -r 8000 -b 16 $convertido",$salida,$exitcode);
                    unlink($output);
                    copy($convertido, $output);
                    unlink($convertido);

                    $query = "UPDATE recordings SET displayname=?,filename=? WHERE id=?";
                    $db->exec($query,array($basefname,'custom/'.$basefname,$insertid));
                    return $insertid;
                } else {
                    $query = "DELETE FROM  recordings WHERE displayname=? AND filename=?";
                    $db->exec($query,array('','custom/new'));
                    return 0;

                }


            }
        }
        return 0;
    }

    public function enabled($data) {
        if($data==1 || $data=="1" || $data==strtolower("on") || $data==strtolower("yes")) { return '1'; } else { return '0'; }
    }

    public function presentation_enabled($data) {
        if($data=='1') { return 'yes'; } else { return 'no'; }
    }

}


