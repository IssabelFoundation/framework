<?php

class users extends rest {
    protected $table           = "users";
    protected $id_field        = 'extension';
    protected $name_field      = 'name';
    protected $extension_field = 'extension';
    protected $dest_field      = 'CONCAT("from-internal",",",extension,",1")';

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


