<?php

class conferences extends rest {
    protected $table      = "meetme";
    protected $id_field   = 'exten';
    protected $extension_field   = 'exten';
    protected $name_field = 'description';
    protected $dest_field = 'CONCAT("from-internal",",",exten,",1")';
    protected $search_field = 'description';
}


