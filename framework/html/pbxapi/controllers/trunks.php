<?php

class trunks extends rest {
    protected $table      = "trunks";
    protected $id_field   = 'trunkid';
    protected $name_field = 'name';
    protected $dest_field = "";
    protected $list_fields = array('tech','channelid');
    protected $extension_field = '';

    protected $field_map = array(
        'channelid'          => 'trunk_name',
        'usercontext'        => 'user_context',
        'maxchans'           => 'maximum_channels',
        'outcid'             => 'outbound_callerid',
        'dialoutprefix'      => 'dialout_prefix',
        'continue'           => 'continue_if_busy',
 

    );
}


