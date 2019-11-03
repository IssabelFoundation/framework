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
  $Id: cidlookup.php, Fri 05 Apr 2019 05:48:47 PM EDT, nicolas@issabel.com
*/

class musiconhold extends rest {

    function __construct($f3, $ami_connect=0, $sql_mapper=1) {
        parent::__construct($f3,0,0);
    }

    public function get($f3, $from_child=0) {

        $paramid = $f3->get('PARAMS.id');
       
        $rows=$this->get_music();
        
        if($paramid<>'') {
            $oneitem = array();
            foreach($rows as $idx=>$data) {
                if($data['category']==$paramid) {
                    $oneitem=array($data); 
                    $this->outputSuccess($oneitem);
                }
            }
        }

        $this->outputSuccess($rows);
    }

    private function get_music($path=null) {
        if ($path === null) {
            $path = '/var/lib/asterisk/moh';
        }
        $i = 1;
        $arraycount = 0;
        $moh  = array();
        $moh['default']=array();

        $ignore = array('.','..','CVS');

        if (is_dir($path)){
            if ($handle = opendir($path)){
                while (false !== ($file = readdir($handle))){
                    if ( !in_array($file,$ignore) && substr($file,0,1)<>'.' || $file=='.random') {
                        if (is_dir("$path/$file")) {
                            $moh[$file] = array();
                            if ($handle2 = opendir("$path/$file")){
                                while (false !== ($file2 = readdir($handle2))){
                                    if ( !in_array($file2,$ignore) && substr($file2,0,1)<>'.' || $file2=='.random' ) {
                                        if (!is_dir("$path/$file/$file2")) {
                                            $moh[$file][]=$file2;
                                        }
                                    }
                                }
                            }

                        } else {
                            $moh['default'][] = $file;
                        }
                    }
                }
                closedir($handle);
            }
        }

        $finalmoh=array();
        foreach($moh as $category=>$files) {
            asort($files);
            if(in_array('.random',$files)) {
                unset($files['.random']);
                $files = array_diff($files,array('.random')); // remove .random file
                $finalmoh[]=array('name'=>$category,'category'=>$category,'files'=>$files,'random'=>'yes');
            } else {
                $finalmoh[]=array('name'=>$category,'category'=>$category,'files'=>$files,'random'=>'no');
            }
        }

        if (isset($moh)) {
//            $finalmoh[]=array('name'=>'none','category'=>'none','files'=>[],'random'=>'no');
            return $finalmoh;
        } else {
            return [];
        }
    }

    public function put($f3,$from_child) {

        $errors = array();

        $category = $f3->get('PARAMS.id');

        if($category=='') {
            $errors[]=array('status'=>'405','detail'=>'Unable to update. Missing record id');
            $this->dieWithErrors($errors);
        }

        $input = $this->parseInputData($f3);

        $category = $input['category'];
        $category = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $category);
        $category = mb_ereg_replace("([\.]{2,})", '', $category);
        $category = preg_replace("/ /","_",$category);
        if($category=='default') { $category=''; } else { $category=$category."/"; }


        $file = '/var/lib/asterisk/moh/'.$category.'.random';

        if($input['random']=='yes') {
            touch($file);
        } else {
            unlink($file);
        }

    }

    public function post($f3,$from_child) {

        $errors = array();

        $loc = $f3->get('REALM');

        $input = $f3->get('POST');

        $files = $f3->get('FILES');

        // post is special as it will accept a file to be uploaded for existing category.. for new categories no files are uploaded firsthand
        if(count($files)>0) {

            $recid = $this->handleUpload($f3);

            if($recid<>'') {
                header("Location: $loc/$recid", true, 201);
            } else {
                $errors[]=array('status'=>'500','detail'=>'Could not process uploaded file');
                $this->dieWithErrors($errors);
            }

        } else {

            $input = $this->parseInputData($f3);

            if(!isset($input['category'])) {
                $errors[]=array('status'=>'422','source'=>'category','detail'=>'Required field missing');
            }

            $category = $input['category'];
            $category = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $category);
            $category = mb_ereg_replace("([\.]{2,})", '', $category);
            $category = preg_replace("/ /","_",$category);

            if($category=='default') { $category=''; } else { $category=$category."/"; }

            $dir = '/var/lib/asterisk/moh/'.$category;
            $dir = substr($dir,0,-1);
            if(file_exists($dir)) {
                $errors[]=array('status'=>'409','detail'=>"Directory $dir already exists");
            }

            if(count($errors)>0) {
                $this->dieWithErrors();
            }

            $ret = mkdir($dir);

            if(!$ret) {
                $errors[]=array('status'=>'405','source'=>$file_to_delete,'detail'=>'Could not create directory '+$dir);
                $this->dieWithErrors($errors);
            }
   
            $file = '/var/lib/asterisk/moh/'.$category.'.random';

            if($input['random']=='yes') {
                touch($file);
            } else {
                if(is_file($file)) {
                    unlink($file);
                }
            }

            $this->applyChanges($input);

            // 201 CREATED
            header("Location: $loc/$category", true, 201);
            die();

        }
        die();

    }

    private function handleUpload($f3) {
        // audio recording upload from web control
        $errors = array();
        $db     = $f3->get('DB');
        $input  = $f3->get('POST');
        $FILES  = $f3->get('FILES');

        $data = json_decode($input['data'],1);

        if(!isset($data['category'])) {
            $errors[]=array('status'=>'422','source'=>'category','detail'=>'Missing required field');
            $this->dieWithErrors($errors);
        }

        $category = $data['category'];
        if($category=='default') { $category=''; } else { $category=$category."/"; }

        if(isset($FILES['file'])) {
            if($FILES['error']==0) {

                $input          = $FILES['file']['tmp_name'];
                $actualfilename = $FILES['file']['name'];
                $ext            = pathinfo($actualfilename, PATHINFO_EXTENSION);
                $basefname      = pathinfo($actualfilename, PATHINFO_FILENAME);
                $fname          = isset($post['filename'])?$post['filename']:$FILES['file']['name'];

                // only allow letters/digits and dot on filenames
                $fname = preg_replace("/[^a-zA-Z0-9\.]+/", "", $fname);
                $basefname = preg_replace("/[^a-zA-Z0-9\.]+/", "", $basefname);

                $output = "/var/lib/asterisk/moh/".$category.$fname;

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
                    return $data['category'];
                } 
                return '';
            }
        }

    }

    function delete($f3,$from_child) {

        $errors=array();

        if($f3->get('PARAMS.id')=='') {
            $errors[]=array('status'=>'405','detail'=>'Cannot delete if no ID is supplied');
            $this->dieWithErrors($errors);
        }
        $id = $f3->get('PARAMS.id');

        if(preg_match("/^file\^/",$id)) {
           // format for removing an individual wav file from disk is file ^ category ^ index
           list ($nada,$category,$id_to_delete) = preg_split("/\^/",$id);

           if($category=='default') { $dircategory=''; } else { $dircategory=$category."/"; }
        
           $rows = $this->get_music();
           foreach($rows as $idx=>$data) {
                if($data['category']==$category) {
                    $file_to_delete = '/var/lib/asterisk/moh/'.$dircategory.$data['files'][$id_to_delete];
                    if(is_file($file_to_delete)) {
                        $ret = unlink($file_to_delete);
                        if(!$ret) {
                            $errors[]=array('status'=>'405','source'=>$file_to_delete,'detail'=>'Could not remove file');
                            $this->dieWithErrors($errors);
                        } 
                    }
                }
            }
        } else {
            $allids = explode(",",$f3->get('PARAMS.id'));
            foreach($allids as $category) {
                $category = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $category);
                $category = mb_ereg_replace("([\.]{2,})", '', $category);
                $category = preg_replace("/ /","_",$category);
                if($category=='default') {
                    $errors[]=array('status'=>'422','detail'=>'Removal of default MOH category is forbidden');
                    $this->dieWithErrors($errors);
                }
                $randomfile = '/var/lib/asterisk/moh/'.$category.'/.random';
                if(is_file($randomfile)) {
                    unlink($randomfile);
                }
                $dir_to_delete = '/var/lib/asterisk/moh/'.$category;
                $ret = rmdir($dir_to_delete);
                if(!$ret) {
                    $errors[]=array('status'=>'405','source'=>$dir_to_delete,'detail'=>'Could not remove directory');
                    $this->dieWithErrors($errors);
                }
            }

            $this->applyChanges($input);

        }

    }


}


