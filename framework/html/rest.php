<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  | Copyright (c) 1997-2003 Palosanto Solutions S. A.                    |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  | Autores: Alberto Santos Flores <asantos@palosanto.com>               |
  +----------------------------------------------------------------------+
  $Id: rest.php,v 1.1 2012/02/07 23:49:36 Alberto Santos Exp $
*/

global $arrConf;
$documentRoot = dirname($_SERVER['SCRIPT_FILENAME']);
require_once "$documentRoot/libs/misc.lib.php";
require_once "$documentRoot/configs/default.conf.php";
require_once "$documentRoot/libs/paloSantoJSON.class.php";
require_once "$documentRoot/libs/paloSantoACL.class.php";

load_default_timezone();

// Verificación de autenticación
// Verificar si el método HTTP es conocido
if (!in_array($_SERVER['REQUEST_METHOD'],
    array('GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'OPTIONS', 'TRACE'))) {
    Header('HTTP/1.1 501 Not Implemented');
    exit;
}

$json = new paloSantoJSON();
header('Content-Type: application/json');

/***********************User authentication********************************/
$pACL = new paloACL($arrConf['issabel_dsn']['acl']);
if(!empty($pACL->errMsg)){
    header("HTTP/1.1 500 Internal Server Error");
    $json->set_status("ERROR");
    $json->set_error("Unable to authenticate due to DB error: ".$pACL->errMsg);
    echo $json->createJSON();
    exit;
}

session_name("issabelSession");
session_start();
if (isset($_SESSION['issabel_user']) && isset($_SESSION['issabel_pass'])) {
    $auth_user = $_SESSION['issabel_user'];
    $auth_md5pass = $_SESSION['issabel_pass'];
    $_SERVER['PHP_AUTH_USER'] = $_SESSION['issabel_user'];
} elseif (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] != '') {
    $auth_user = $_SERVER['PHP_AUTH_USER'];
    $auth_md5pass = md5($_SERVER['PHP_AUTH_PW']);
} else {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="IssabelWebService"');
    $json->set_status("ERROR");
    $json->set_error("This method requires authentication");
    echo $json->createJSON();
    exit;
}

if(!$pACL->authenticateUser($auth_user, $auth_md5pass)){
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="IssabelWebService"');
    $json->set_status("ERROR");
    $json->set_error("Invalid username or password");
    echo $json->createJSON();
    exit;
}
/*************End of authentication*******************************************/

//Verifico si se ha pasado una ruta hacia un recurso
if(!isset($_SERVER["PATH_INFO"])){
    header('HTTP/1.1 400 Bad Request');
    $json->set_status("ERROR");
    $json->set_error("You need to specify a menu id");
    echo $json->createJSON();
    exit;
}

$pathList = explode("/",$_SERVER["PATH_INFO"]);
array_shift($pathList);
//Como estándar se ha decidido que el primer elemento de la ruta será el id del módulo que contenga el recurso
$moduleId = array_shift($pathList);

//Verifico si el módulo existe
if(!file_exists("$documentRoot/modules/$moduleId")){
    header('HTTP/1.1 404 Not Found');
    $json->set_status("ERROR");
    $json->set_error("The menu id specified was not found");
    echo $json->createJSON();
    exit;
}

//El segundo elemento de la ruta será el recurso
$resource = array_shift($pathList);

if(is_null($resource)){
    header('HTTP/1.1 400 Bad Request');
    $json->set_status("ERROR");
    $json->set_error("You need to specify a resource");
    echo $json->createJSON();
    exit;
}

//Verifico si el recurso existe (Como estándar los recursos se crearán dentro de la carpeta scenarios/rest de cada módulo)
if(!file_exists("$documentRoot/modules/$moduleId/scenarios/rest/$resource.class.php")){
    header("HTTP/1.1 501 Not Implemented");
    $json->set_status("ERROR");
    $json->set_error("The resource $resource have not been implemented");
    echo $json->createJSON();
    exit;
}

//Se incluye el archivo que contiene el recurso
require_once "$documentRoot/modules/$moduleId/scenarios/rest/$resource.class.php";

//Se instancia un objeto al recurso. El recurso siempre recibe la ruta que viene despues del nombre del mismo, es decir si el llamado al recurso es http://1.2.3.4/rest.php/id_modulo/recurso/ejemplo/otroEjemplo se le pasara un arreglo que contiene a "ejemplo" en la posición 0 y "otroEjemplo" en la posición 1
$resourceObject = new $resource($pathList);

//Se obtiene el objeto URI. Para ello se llama a la función URIObject la cual debe estar implementada en todos los recursos.

$uriObject = $resourceObject->URIObject();
if(is_null($uriObject)){
    header('HTTP/1.1 404 Not Found');
    $json->set_status("ERROR");
    $json->set_error('No resource was found under specified URI');
    echo $json->createJSON();
    exit;
}

// Verificar si el método es válido para este objeto
$sMetodo = 'HTTP_'.$_SERVER['REQUEST_METHOD'];
if (!method_exists($uriObject, $sMetodo)) {
    Header('HTTP/1.1 405 Method Not Allowed');
    $uriObject->HTTP_OPTIONS(); // Para agregar la cabecera Allow
    exit;
}

echo $uriObject->$sMetodo();
?>
