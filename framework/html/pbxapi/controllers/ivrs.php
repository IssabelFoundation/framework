<?php

class ivrs extends rest {
    protected $table      = "pbx_ivr";
    protected $extension_field = 'extension';
    protected $dest_field = 'CONCAT("pbx-ivr-",id,",s,1")';
}
