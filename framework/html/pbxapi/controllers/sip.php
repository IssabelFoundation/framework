<?php

class sip extends rest {
    protected $table           = "sip";
    protected $id_field        = 'id';
    protected $name_field      = 'keyword';
    protected $extension_field = '';
    protected $dest_field      = '';
    protected $list_fields  = array('keyword','data');

    function delete($f3) {
        // Because the devices table in IssabelPBX does not have a primary key, we have to override
        // the rest class DELETE method and pass the condition as a filter
        //
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


