<?php


class voicemails extends rest {
    protected $table      = "users";
    protected $id_field   = 'extension';
    protected $name_field = 'name';
    protected $dest_field = 'CONCAT("from-internal",",","*",extension,",1")';
    protected $extension_field = 'extension';
    protected $condition = array('voicemail=?','default');

    function post($f3) {
    }

    function put($f3) {
    }

    function delete($f3) {
    }

    function search($f3) {
    }
}

