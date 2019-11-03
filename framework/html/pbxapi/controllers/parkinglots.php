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
  $Id: parkinglots.php, Tue 04 Sep 2018 09:54:43 AM EDT, nicolas@issabel.com
*/

class parkinglots extends rest {

    protected $table           = "parkplus";
    protected $id_field        = 'id';
    protected $name_field      = 'name';
    protected $extension_field = 'parkext';
    protected $list_fields     = array('parkext','parkpos','numslots');
    protected $search_field    = 'name';

    protected $provides_destinations = false;

    protected $field_map = array(
        'parkext'            => 'extension',
        'parkpos'            => 'starting_position',
        'numslots'           => 'number_of_slots',
        'dest'               => 'destination',
        'defaultlot'         => 'default',
        'parkingtime'        => 'timeout',
        'type'               => 'type',
        'parkedmusicclass'   => 'music_on_hold_class',
        'generatehints'      => 'generate_hints',
        'findslot'           => 'find_slot',
        'alertinfo'          => 'alert_info',
        'announcement_id'    => 'announcement_id',
        'comebacktoorigin'   => 'come_back_to_origin',
        'cidpp'              => 'callerid_prepend',
        'autocidpp'          => 'auto_callerid_prepend',
        'parkedcalltransfers'=> 'transfer_capability',
        'parkedcallreparking'=> 'reparking_capability',
        'parkedplay'         => 'pickup_courtesy_tone',
        // 'generatefc'         => 'yes',   * not used any more *?
    );

    protected $transforms = array( 
    );

    protected $presentationTransforms = array( 
    );

    public function put($f3,$from_child) {

        $db = $f3->get('DB');

        parent::put($f3,1);

        $input = $this->parseInputData($f3);

        // we need to make sure only one record is marked as default
        $parkid = $f3->get('PARAMS.id');

        if(isset($input['default'])) {
            $default = ($input['default']=='yes')?1:0;

            if($default==1) {
                // marked as default, make all as not default and update to default this very one
                $db->exec("UPDATE parkplus SET defaultlot='no'");
                $db->exec("UPDATE parkplus SET defaultlot='yes' WHERE id=?",array($parkid));
            } else {
                // if no record is marked as default, mark the first one
                $rows = $db->exec("SELECT id FROM parkplus WHERE defaultlot='yes'");
                if($db->count()==0) {
                    $db->exec("UPDATE parkplus SET defaultlot='yes' ORDER by id LIMIT 1");
                }
            }
        }

        $this->applyChanges($input);
    }

    public function post($f3, $from_child=0) {

        $db = $f3->get('DB');

        $input = $this->parseInputData($f3);

        $parkid = parent::post($f3,1);

        if(isset($input['default'])) {
            $default = ($input['default']=='yes')?1:0;

            if($default==1) {
                // marked as default, make all as not default and update to default this very one
                $db->exec("UPDATE parkplus SET defaultlot='no'");
                $db->exec("UPDATE parkplus SET defaultlot='yes' WHERE id=?",array($parkid));
            } else {
                // if no record is marked as default, mark the first one
                $db->exec("SELECT id FROM parkplus WHERE defaultlot='yes'");
                if($db->count()==0) {
                    $db->exec("UPDATE parkplus SET defaultlot='yes' ORDER by id LIMIT 1");
                }
            }
        }

        $this->applyChanges($input);

        $loc = $f3->get('REALM');
        header("Location: $loc/".$appid, true, 201);
        die();

    }

    public function delete($f3, $from_child) {

        $errors = array();
        $db = $f3->get('DB');

        $allids = $f3->get('PARAMS.id');

        $arrids  = preg_split("/,/",$allids);

        foreach($arrids as $oneid) {
            $db->exec("SELECT defaultlot FROM parkplus WHERE id=? AND defaultlot='yes'",array($oneid));
            if($db->count()>0) {
                // cannot delete default lot, die
                $errors[]=array('status'=>'400','detail'=>'Default parking lot cannot be removed');
                $this->dieWithErrors($errors);
                die();
            }
        }

        parent::delete($f3,1);

        $this->applyChanges($input);
 
    }


}


