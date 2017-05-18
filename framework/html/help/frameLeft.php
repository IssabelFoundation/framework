<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
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
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: frameLeft.php,v 1.2 2007/09/07 23:05:29 gcarrillo Exp $ */


include_once("../libs/paloSantoTree.class.php");
include_once("../libs/paloSantoDB.class.php");
include_once("../libs/paloSantoACL.class.php");
include_once("../libs/misc.lib.php");
include_once("../configs/default.conf.php");
include_once("../libs/paloSantoMenu.class.php");

load_default_timezone();

session_name("elastixSession");
session_start();

global $arrConf;

$pDB = new paloDB($arrConf['elastix_dsn']['acl']);

$pDBMenu = new paloDB($arrConf['elastix_dsn']['menu']);
$pMenu = new paloMenu($pDBMenu);
$arrMenu = $pMenu->cargar_menu();

if(!empty($pDB->errMsg)) {
    echo "ERROR DE DB: $pDB->errMsg <br>";
}

$pACL = new paloACL($pDB);

$arrTmp = array();

if(!empty($_SESSION['elastix_user'])) {
    $idUser = $pACL->getIdUser($_SESSION['elastix_user']);
    //- TODO: Mejorar el siguiente bloque. Seguro debe de haber una forma mas
    //-       eficiente de hacerlo
    //- Primero me barro todos los submenus
    foreach($arrMenu as $idMenu=>$arrMenuItem) {
        if(!empty($arrMenuItem['IdParent'])) {
            if ($pACL->isUserAuthorizedById($idUser, "access", $idMenu)) {
                $arrSubmenu[$idMenu] = $arrMenuItem;
                $arrMenuFiltered[$idMenu] = $arrMenuItem;
            }
        }
    }
    //- Ahora me barro el menu principal
    foreach($arrMenu as $idMenu=>$arrMenuItem) {
        if(empty($arrMenuItem['IdParent'])) {
            foreach($arrSubmenu as $idSubMenu=>$arrSubMenuItem) {
                if($arrSubMenuItem['IdParent']==$idMenu) {
                    $arrMenuFiltered[$idMenu] = $arrMenuItem;
                }
            }
        }
    }
} else {
    $arrMenuFiltered = $arrMenu;
}


foreach($arrMenuFiltered as $id => $menu) {
    $arrTmp = array();
    $arrTmp['id_nodo']   = $id;

    if($menu['HasChild']){
        if(empty($menu['IdParent']))
            $arrTmp['id_parent'] = "root";
        else
            $arrTmp['id_parent'] = $menu['IdParent'];
        $arrTmp['tipo']      = "C";
    } else {
        $arrTmp['tipo']      = "A";
        $arrTmp['id_parent'] = $menu['IdParent'];
    }
    $arrTmp['orden']     = 1;
    $arrTmp['nombre']    = $menu['Name'];
    $arrTmp['url']       = $id . "." . $menu['IdParent'] . "hlp.htm";
    $arrTmp['keywords']  = "";

    $arrNodos[] = $arrTmp;
}

$oPt = new paloTree($arrNodos);

$nodeserial = isset($_GET['nodeserial'])?$_GET['nodeserial']:'';

$oPt->actualizarNodosAbiertos($_GET['id_nodo'], explode(',',urldecode($nodeserial)));

// Just to make sure that the parent node is open
$idParent=$oPt->obtenerParent($_GET['id_nodo']);
$oPt->tocarNodo($idParent, 1);

$nodeserial=urlencode(implode(',',$oPt->obtenerNodosAbiertos()));
$oPt->setURLBase("frameLeft.php?nodeserial=$nodeserial");
$oPt->setRutaImage("../images");

echo "<html><head>";
echo "<link rel='stylesheet' href='../themes/default/styles.css'>";
echo "</head><body style=\"background-image: url(../images/bgGrayHoriz.gif); background-color: #f6f6f6; background-repeat: repeat-y;\">";
echo $oPt->dibujaArbol("root");
echo "</body></html>";

?>