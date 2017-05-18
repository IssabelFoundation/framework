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
  $Id: frameRight.php,v 1.1.1.1 2007/07/06 21:31:56 gcarrillo Exp $ */
include_once("../libs/misc.lib.php");
include_once "../configs/default.conf.php";

load_default_timezone();

session_name("elastixSession");
session_start();

// Load smarty
$smarty = getSmarty($arrConf['mainTheme'], $arrConf['basePath']);
$smarty->assign("THEMENAME", $arrConf['mainTheme']);

// Nombres válidos de módulos son alfanuméricos y subguión
if (!preg_match('/^\w+$/', $_GET['id_nodo'])) {
    unset($_GET['id_nodo']);
}

if(!empty($_GET['id_nodo'])){
    $idMenuMostrar = $_GET['id_nodo'];
    if(!empty($_GET['name_nodo'])){
        $smarty->assign("node_name", htmlentities($_GET['name_nodo'], ENT_COMPAT, 'UTF-8'));
    }

    // Si no existe el archivo de ayuda y se trata de un menu "padre",
    // muestro el menu hijo que encuentre primero
    $sRuta = rutaArchivoAyuda($idMenuMostrar);
    if (is_null($sRuta)) {
    	$idMenuMostrar = menuHijoPorOmision($idMenuMostrar);
        $sRuta = rutaArchivoAyuda($idMenuMostrar);
    }
    if (is_null($sRuta)) {
    	echo '<html><body>The help file for selected menu does not exist.</body></html>';
    } else {
       $smarty->assign("node_id", $idMenuMostrar);
       $smarty->display($sRuta);
    }
} else {
    echo "The selected menu is not valid.";
}

function menuHijoPorOmision($idMenu)
{
    $arrMenu = array();
    if(isset($_SESSION['elastix_user_permission']))
        $arrMenu = $_SESSION['elastix_user_permission'];
    if(is_array($arrMenu))
    {
        foreach($arrMenu as $k => $menu) {
            if($menu['IdParent']==$idMenu) {
                echo "<h1>".$menu['Name']."</h1>";
				return $k;
                break;
            }
        }
    }
    return false;
}

function obtenerMenuPadre($idMenu)
{
    $arrMenu = $_SESSION['elastix_user_permission'];
    return $arrMenu[$idMenu]['IdParent'];
}

function rutaArchivoAyuda($idMenu)
{
    $serverDir = dirname($_SERVER['SCRIPT_FILENAME']).'/..';

    $lang = get_language("$serverDir/");
    $listaRutas = array();

    $listaRutas[] = "$serverDir/modules/$idMenu/help/$lang.hlp";
    if ($lang != 'en') $listaRutas[] = "$serverDir/modules/$idMenu/help/en.hlp";
    $listaRutas[] = "$serverDir/modules/$idMenu/help/$idMenu.hlp";
    $listaRutas[] = "$serverDir/help/content/{$lang}_{$idMenu}.hlp";
    if ($lang != 'en') $listaRutas[] = "$serverDir/help/content/en_{$idMenu}.hlp";
    $listaRutas[] = "$serverDir/help/content/$idMenu.hlp";

    foreach ($listaRutas as $sRuta) {
        if (file_exists($sRuta)) return $sRuta;
    }
    return NULL;
}

?>
