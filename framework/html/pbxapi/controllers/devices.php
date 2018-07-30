<?php

class devices extends rest {
    protected $table           = "devices";
    protected $id_field        = 'id';
    protected $name_field      = 'description';
    protected $extension_field = '';
    protected $dest_field      = '';
    protected $list_fields  = array('tech','dial','devicetype','user','description','emergency_cid');

    // Because the devices table in IssabelPBX does not have a primary key, we have to override
    // the rest class DELETE methods and pass the condition as a filter

    
    function delete($f3) {

        if($f3->get('PARAMS.id')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        $allids = explode(",",$f3->get('PARAMS.id'));

        foreach($allids as $oneid) {

            $this->data->load(array($this->id_field.'=?',$oneid));

            if ($this->data->dry()) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
                die();
            }

            try {
                $this->data->erase($this->id_field."=".$oneid);
            } catch(\PDOException $e) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                die();
            }

        }

    }

}


