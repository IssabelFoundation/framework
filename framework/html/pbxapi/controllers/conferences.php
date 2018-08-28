<?php

class conferences extends rest {
    protected $table           = 'meetme';
    protected $id_field        = 'exten';
    protected $extension_field = 'exten';
    protected $name_field      = 'description';
    protected $dest_field      = 'CONCAT("ext-meetme",",",exten,",1")';
    protected $search_field    = 'description';
    protected $initial_exten_n = '500';

    protected $field_map = array(
        'userpin'             => 'user_pin',
        'adminpin'            => 'admin_pin',
        'joinmsg_id'          => 'join_message_id',
        'users'               => 'max_participants'
    );

    protected $defaults = array(
        'admin_pin'        => '',
        'user_pin'         => '',
        'options'          => '',
        'music'            => 'inherit',
        'join_message_id'  => 0,
        'max_participants' => 10
    );
}


