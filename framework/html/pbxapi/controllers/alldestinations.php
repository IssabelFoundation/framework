<?php

class alldestinations extends rest {
    protected $table      = "alldestinations";
    protected $id_field   = 'extension';
    protected $name_field = 'name';
    protected $extension_field = 'extension';
    protected $dest_field = 'CONCAT(context,",",extension,",1")';
    protected $list_fields  = array('type','context');
}


