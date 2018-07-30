<?php

class ivroptions extends rest {

    protected $table           = "pbx_ivr_options";
    protected $name_field      = 'option';
    protected $dest_field      = 'dest';
    protected $search_field    = 'ivr_id';
    protected $extension_field = '';
    protected $list_fields  = array('pattern');

    public function ivr($f3) {

        // Almost exact same, but we want to search by ivr_id exactly, not likekly
        // So we can get with one request all options from a particular IVR

        if($f3->get('PARAMS.term')=='') {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed', true, 405);
            die();
        }

        $this->condition = array($this->search_field.'=?',$f3->get('PARAMS.term'));
        $this->get($f3);

    }
}
