<?php

class ringgroups extends rest {
    protected $table      = "ringgroups";
    protected $id_field   = 'grpnum';
    protected $name_field = 'description';
    protected $extension_field = 'grpnum';
    protected $dest_field = 'CONCAT("from-internal",",",grpnum,",1")';
}


