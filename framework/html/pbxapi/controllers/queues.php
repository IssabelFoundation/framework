<?php

class queues extends rest {
    protected $table           = "queues_config";
    protected $id_field        = 'extension';
    protected $name_field      = 'descr';
    protected $search_field    = 'descr';
    protected $extension_field = 'extension';
    protected $dest_field = 'CONCAT("from-internal",",",extension,",1")';
}


