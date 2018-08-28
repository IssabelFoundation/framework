<?php

class trunks extends rest {
    protected $table      = "trunks";
    protected $id_field   = 'trunkid';
    protected $name_field = 'name';
    protected $dest_field = "";
    protected $list_fields = array('tech','channelid');
    protected $extension_field = '';
}


