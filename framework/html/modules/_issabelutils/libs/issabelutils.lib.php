<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
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
*/

/**
 * Función para obtener un detalle de los rpms que se encuentran instalados en el sistema.
 *
 *
 * @return  mixed   NULL si no se reconoce usuario, o el DNS con clave resuelta
 */
function obtenerDetallesRPMS()
{
    $packageClass = array(
        'Kernel'    =>  NULL,
        'Issabel'   =>  array('issabel*'),
        'RoundCubeMail' =>  array('RoundCubeMail'),
        'Mail'          =>  array('postfix', 'cyrus-imapd'),
        'IM'            =>  array('openfire'),
        'IssablPBX'     =>  array('issabelPBX'),
        'Asterisk'      =>  array('asterisk*'),
        'FAX'           =>  array('hylafax', 'iaxmodem'),
        'DRIVERS'       =>  array('dahdi', 'rhino', 'wanpipe-util'),

    );
    $sCommand = 'rpm -qa  --queryformat "%{name} %{version} %{release}\n"';
    foreach ($packageClass as $packageLists) {
    	if (is_array($packageLists)) $sCommand .= ' '.implode(' ', array_map('escapeshellarg', $packageLists));
    }
    $sCommand .= ' | sort -V';
    $output = $retval = NULL;
    exec($sCommand, $output, $retval);
    $packageVersions = array();
    foreach ($output as $s) {
    	$fields = explode(' ', $s);
        $packageVersions[$fields[0]] = $fields;
    }

    $result = array();
    foreach ($packageClass as $sTag => $packageLists) {
    	if (!isset($result[$sTag])) $result[$sTag] = array();
        if ($sTag == 'Kernel') {
    		// Caso especial
            $result[$sTag][] = explode(' ', trim(`uname -s -r -i`));
    	} elseif ($sTag == 'Issabel') {
    		// El paquete Issabel debe ir primero
            if (isset($packageVersions['issabel']))
                $result[$sTag][] = $packageVersions['issabel'];
            foreach ($packageVersions as $packageName => $fields) {
            	if (substr($packageName, 0, 8) == 'issabel-')
                    $result[$sTag][] = $fields;
            }
        } elseif ($sTag == 'Asterisk') {
            foreach ($packageVersions as $packageName => $fields) {
                if (substr($packageName, 0, 8) == 'asterisk')
                    $result[$sTag][] = $fields;
            }
    	} else {
    		foreach ($packageLists as $packageName)
                $result[$sTag][] = isset($packageVersions[$packageName])
                    ? $packageVersions[$packageName]
                    : array($packageName, '(not installed)', ' ');
    	}
    }
    return $result;
}

function setUserPassword()
{
    global $arrConf;
    require_once "libs/paloSantoACL.class.php";

    $old_pass   = getParameter("oldPassword");
    $new_pass   = getParameter("newPassword");
    $new_repass = getParameter("newRePassword");
    $arrResult  = array();
    $arrResult['status'] = FALSE;
    if ($old_pass == "") {
        $arrResult['msg'] = _tr("Please write your current password.");
        return $arrResult;
    }
    if ($new_pass == "" || $new_repass == "") {
        $arrResult['msg'] = _tr("Please write the new password and confirm the new password.");
        return $arrResult;
    }
    if ($new_pass != $new_repass) {
        $arrResult['msg'] = _tr("The new password doesn't match with retype new password.");
        return $arrResult;
    }

    $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
    $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);
    if($uid===FALSE)
        $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    else{
        // verificando la clave vieja
        $val = $pACL->authenticateUser ($user, md5($old_pass));
        if($val === TRUE){
            $status = $pACL->changePassword($uid, md5($new_pass));
            if($status){
                $arrResult['status'] = TRUE;
                $arrResult['msg'] = _tr("Issabel password has been changed.");
                $_SESSION['issabel_pass'] = md5($new_pass);
            }else{
                $arrResult['msg'] = _tr("Impossible to change your Issabel password.");
            }
        }else{
            $arrResult['msg'] = _tr("Impossible to change your Issabel password. User does not exist or password is wrong");
        }
    }
    return $arrResult;
}

function searchModulesByName()
{
    include_once "libs/JSON.php";
    $json = new Services_JSON();

    $name = NULL;
    if (isset($_REQUEST['term'])) $name = $_REQUEST['term'];
    if (isset($_REQUEST['name_module_search'])) $name = $_REQUEST['name_module_search'];
    $result = array();
    //$arrIdMenues = array();
    $lang=get_language();
    global $arrLang;

    // obteniendo los id de los menus permitidos
    global $arrConf;
    $pACL = new paloACL($arrConf['issabel_dsn']['acl']);
    $pMenu = new paloMenu($arrConf['issabel_dsn']['menu']);
    $arrSessionPermissions = $pMenu->filterAuthorizedMenus($pACL->getIdUser($_SESSION['issabel_user']));
    $arrIdMenues = array();
    foreach($arrSessionPermissions as $key => $value){
        $arrIdMenues[] = $value['id']; // id, IdParent, Link, Name, Type, order_no, HasChild
    }

    $parameter_to_find = array(); // arreglo con los valores del name dada la busqueda
    // el metodo de busqueda de por nombre sera buscando en el arreglo de lenguajes y obteniendo su $key para luego buscarlo en la base de
    // datos menu.db
    if($lang != "en"){ // entonces se adjunta la busqueda con el arreglo de lenguajes en ingles
        foreach($arrLang as $key=>$value){
            $langValue    = strtolower(trim($value));
            $filter_value = strtolower(trim($name));
            if($filter_value!=""){
                if(preg_match("/^[[:alnum:]| ]*$/",$filter_value))
                    if (strpos($langValue, $filter_value) !== FALSE)
                        $parameter_to_find[] = $key;
            }
        }
    }
    $parameter_to_find[] = $name;

    // buscando en la base de datos acl.db tabla acl_resource con el campo description
    $arrResult = $pACL->getListResources(25, 0,
    	empty($parameter_to_find) ? $name : $parameter_to_find);

    foreach($arrResult as $key2 => $value2){
        // leyendo el resultado del query
        if(in_array($value2["name"], $arrIdMenues)){
            $arrMenu[/*'caption'*/'label'] = _tr($value2["description"]);
            $arrMenu['value']   = $value2["name"];
            $result[] = $arrMenu;
        }
    }

    header('Content-Type: application/json');
    return $json->encode($result);
}

function changeMenuColorByUser()
{
    global $arrConf;
    require_once "libs/paloSantoACL.class.php";

    $color = getParameter("menuColor");
    $arrResult  = array();
    $arrResult['status'] = FALSE;

    if($color == ""){
       $color = "#454545";
    }

    $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
    $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($user);

    if ($uid === FALSE)
        $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    else {
        /* Desde el commit SVN #3231 hecho por (quien ya sabemos) el valor de
         * menuColor se ha estado almacenando bajo el id_resource=19. Ahora se
         * implementa correctamente que el recurso que posee el color de menú
         * es themes_system. */
        $arrResult['status'] = $pACL->saveUserProfileProperty($uid, 'themes_system', 'menuColor', $color);
        $arrResult['msg'] = $arrResult['status'] ? _tr("OK") : _tr("ERROR DE DB: ").$pACL->errMsg;
    }
    return $arrResult;
}

function putMenuAsBookmark($menu)
{
    global $arrConf;
    require_once "libs/paloSantoACL.class.php";

    $arrResult = array(
        'status'    =>  FALSE,
        'data'      =>  array("action" => "none", "menu" => "$menu"),
        'msg'       =>  _tr("Please your session id does not exist. Refresh the browser and try again."),
    );
    if ($menu == '' || !isset($_SESSION['issabel_user'])) return $arrResult;

    $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
    $pACL = new paloACL($pdbACL);
    $uid = $pACL->getIdUser($_SESSION['issabel_user']);
    if ($uid === FALSE) return $arrResult;

    $id_resource = $pACL->getResourceId($menu);
    $resource = $pACL->getResources($id_resource);
    $exist = false;
    $bookmarks = <<<SQL_LEER_BOOKMARKS_ACTUALES
SELECT aus.id AS id, ar.id AS id_menu, ar.name AS name, ar.description AS description
FROM acl_user_shortcut aus, acl_resource ar
WHERE id_user = ? AND type = 'bookmark' AND ar.id = aus.id_resource
ORDER BY aus.id DESC
SQL_LEER_BOOKMARKS_ACTUALES;
    $arr_result1 = $pdbACL->fetchTable($bookmarks, TRUE, array($uid));
    if ($arr_result1 === FALSE) return $arrResult;

    //$arrIDS = array();
    foreach ($arr_result1 as $key => $value) {
        if($value['id_menu'] == $id_resource)
            $exist = true;
    }
    if (!$exist && count($arr_result1) > 4) {
        $arrResult['msg'] = _tr('The bookmark maximum is 5. Please remove one in order to add this bookmark');
        return $arrResult;
    }

    $arrResult['data'] = array(
        'action'        =>  ($exist ? 'delete' : 'add'),
        'menu'          =>  _tr($resource[0][2]),
        'idmenu'        =>  $id_resource,
        'menu_session'  =>  $menu,
    );
    $query = $exist
        ? 'DELETE FROM acl_user_shortcut WHERE id_user = ? AND id_resource = ? AND type = ?'
        : 'INSERT INTO acl_user_shortcut(id_user, id_resource, type) VALUES(?, ?, ?)';
    $pdbACL->beginTransaction();
    $r = $pdbACL->genQuery($query, array($uid, $id_resource, "bookmark"));
    if (!$r) {
        $pdbACL->rollBack();
        $arrResult['msg'] = (
            $exist
                ? _tr('Bookmark cannot be removed. Please try again or contact with your Issabel administrator and notify the next error')
                : _tr('Bookmark cannot be added. Please try again or contact with your Issabel administrator and notify the next error')
            ).': '.$pdbACL->errMsg;
    } else {
        $pdbACL->commit();
        $arrResult['status'] = TRUE;
        $arrResult['msg'] = $exist ? _tr('Bookmark has been removed.') : _tr('Bookmark has been added.');
    }

    return $arrResult;
}

/**
 * Funcion que se encarga de guardar o editar una nota de tipo sticky note.
 *
 * @return array con la informacion como mensaje y estado de resultado
 * @param string $menu nombre del menu al cual se le va a agregar la nota
 * @param string $description contenido de la nota que se desea agregar o editar
 * @param string $popup nombre del popup
 *
 * @author Eduardo Cueva
 * @author ecueva@palosanto.com
 */
function saveStickyNote($menu, $description, $popup)
{
    global $arrConf;
    require_once "libs/paloSantoACL.class.php";
    $arrResult['status'] = FALSE;
    $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    if($menu != ""){
        $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
        //global $arrConf;
        $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
        $pACL = new paloACL($pdbACL);
        $id_resource = $pACL->getResourceId($menu);
        $uid = $pACL->getIdUser($user);
        $date_edit = date("Y-m-d h:i:s");
        if($uid!==FALSE){
            $exist = false;
            $query = "SELECT * FROM sticky_note WHERE id_user = ? AND id_resource = ?";
            $arr_result1 = $pdbACL->getFirstRowQuery($query, TRUE, array($uid, $id_resource));
            if($arr_result1 !== FALSE && count($arr_result1) > 0)
                $exist = true;

            if($exist){
                $pdbACL->beginTransaction();
                $query = "UPDATE sticky_note SET description = ?, date_edit = ?, auto_popup = ? WHERE id_user = ? AND id_resource = ?";
                $r = $pdbACL->genQuery($query, array($description, $date_edit, $popup, $uid, $id_resource));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your Issabel administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = "";
                    return $arrResult;
                }
            }else{
                $pdbACL->beginTransaction();
                $query = "INSERT INTO sticky_note(id_user, id_resource, date_edit, description, auto_popup) VALUES(?, ?, ?, ?, ?)";
                $r = $pdbACL->genQuery($query, array($uid, $id_resource, $date_edit, $description, $popup));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your Issabel administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = "";
                    return $arrResult;
                }
            }
        }
    }
    return $arrResult;
}

function saveNeoToggleTabByUser($menu, $action_status)
{
    global $arrConf;

    include_once "libs/paloSantoACL.class.php";
    $arrResult['status'] = FALSE;
    $arrResult['msg'] = _tr("Please your session id does not exist. Refresh the browser and try again.");
    if($menu != ""){
        $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
        $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
        $pACL = new paloACL($pdbACL);
        $uid = $pACL->getIdUser($user);
        if($uid!==FALSE){
            $exist = false;
            $togglesTabs = "SELECT * FROM acl_user_shortcut WHERE id_user = ? AND type = 'NeoToggleTab'";
            $arr_result1 = $pdbACL->getFirstRowQuery($togglesTabs, TRUE, array($uid));
            if($arr_result1 !== FALSE && count($arr_result1) > 0)
                $exist = true;

            if($exist){
                $pdbACL->beginTransaction();
                $query = "UPDATE acl_user_shortcut SET description = ? WHERE id_user = ? AND type = ?";
                $r = $pdbACL->genQuery($query, array($action_status, $uid, "NeoToggleTab"));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your Issabel administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = _tr("Request has been sent.");
                    return $arrResult;
                }
            }else{
                $pdbACL->beginTransaction();
                $query = "INSERT INTO acl_user_shortcut(id_user, id_resource, type, description) VALUES(?, ?, ?, ?)";
                $r = $pdbACL->genQuery($query, array($uid, $uid, "NeoToggleTab", $action_status));
                if(!$r){
                    $pdbACL->rollBack();
                    $arrResult['status'] = FALSE;
                    $arrResult['msg'] = _tr("Request cannot be completed. Please try again or contact with your Issabel administrator and notify the next error: ").$pdbACL->errMsg;
                    return $arrResult;
                }else{
                    $pdbACL->commit();
                    $arrResult['status'] = TRUE;
                    $arrResult['msg'] = _tr("Request has been sent.");
                    return $arrResult;
                }
            }
        }
    }
    return $arrResult;
}

