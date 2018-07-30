<?php
$f3=require('lib/base.php');
$f3->set('AUTOLOAD','models/; controllers/');
$f3->set('DEBUG',255);

if(is_file("/etc/issabel.conf")) {
    $data    = parse_ini_file("/etc/issabel.conf");
    $dbpass  = $data['mysqlrootpwd'];
    $mgrpass = $data['amiadminpwd'];
}

$options = array(
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // generic attribute
    \PDO::ATTR_PERSISTENT => TRUE,  // we want to use persistent connections
    \PDO::MYSQL_ATTR_COMPRESS => TRUE, // MySQL-specific attribute
);

$f3->set('MGRPASS',$mgrpass);
$f3->set('DB', new DB\SQL( 'mysql:host=localhost;port=3306;dbname=asterisk', 'root', $dbpass, $options));

$f3->set('JWT_KEY', 'da893kasdfam43k29akdkfaFFlsdfhj23rasdf');
$f3->set('JWT_EXPIRES', 60 * 1440);

$f3->route('GET /','help->display');

$f3->map('/@controller','@controller');
$f3->map('/@controller/@id','@controller');

$f3->route('GET /@controller/search/@term','@controller->search');

$f3->run();
