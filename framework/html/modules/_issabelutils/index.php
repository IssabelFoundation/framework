<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.0-16                                               |
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
  $Id: index.php,v 1.1 2007/01/09 23:49:36 alex Exp $
*/
require_once "libs/paloSantoJSON.class.php";

function _moduleContent(&$smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/issabelutils.lib.php";

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    load_language_module($module_name);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/" . $templates_dir . '/' . $arrConf['theme'];

    Header('Content-Type: application/json');

    $smarty->assign('module_name', $module_name);
    $sFuncName = 'handleJSON_'.getParameter('action');
    if (function_exists($sFuncName))
        return $sFuncName($smarty, $local_templates_dir, $module_name);

    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status('false');
    $jsonObject->set_error(_tr('Undefined utility action'));
    return $jsonObject->createJSON();
}

function handleJSON_dialogRPM($smarty, $local_templates_dir, $module_name)
{
    $smarty->assign(array(
        'VersionDetails'    =>  _tr('VersionDetails'),
        'VersionPackage'    =>  _tr('VersionPackage'),
        'textMode'          =>  _tr('textMode'),
        'htmlMode'          =>  _tr('htmlMode'),
    ));

    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_message(array(
        'title' =>  _tr('VersionPackage'),
        'html'  =>  $smarty->fetch("$local_templates_dir/_rpms_version.tpl"),
    ));
    return $jsonObject->createJSON();
}

function handleJSON_versionRPM($smarty, $local_templates_dir, $module_name)
{
    $json = new Services_JSON();
    return $json->encode(obtenerDetallesRPMS());
}

function handleJSON_dialogPasswordIssabel($smarty, $local_templates_dir, $module_name)
{
    $smarty->assign(array(
        'CURRENT_PASSWORD'      =>  _tr('Current Password'),
        'NEW_PASSWORD'          =>  _tr('New Password'),
        'RETYPE_PASSWORD'       =>  _tr('Retype New Password'),
        'CHANGE_PASSWORD_BTN'   =>  _tr('Change'),
        "CURRENT_PASSWORD_ALERT"    =>  _tr("Please write your current password."),
        "NEW_RETYPE_PASSWORD_ALERT" =>  _tr("Please write the new password and confirm the new password."),
        "PASSWORDS_NOT_MATCH"       =>  _tr("The new password doesn't match with retype new password."),
    ));
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_message(array(
        'title' =>  _tr('Change Issabel Password'),
        'html'  =>  $smarty->fetch("$local_templates_dir/_change_password.tpl"),
    ));
    return $jsonObject->createJSON();
}

function handleJSON_changePasswordIssabel($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = setUserPassword();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    $jsonObject->set_error($output['msg']);
    return $jsonObject->createJSON();
}

function handleJSON_search_module($smarty, $local_templates_dir, $module_name)
{
    return searchModulesByName();
}

function handleJSON_changeColorMenu($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $output = changeMenuColorByUser();
    $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
    $jsonObject->set_error($output['msg']);
    return $jsonObject->createJSON();
}

function handleJSON_addBookmark($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('false');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $output = putMenuAsBookmark($id_menu);
        if(getParameter('action') == 'deleteBookmark') $output["data"]["menu_url"] = $id_menu;
        $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
        $jsonObject->set_error($output['msg']);
        $jsonObject->set_message($output['data']);
    }

    return $jsonObject->createJSON();
}

function handleJSON_deleteBookmark($smarty, $local_templates_dir, $module_name)
{
    // La función subyacente agrega el bookmark si no existe, o lo quita si existe
    return handleJSON_addBookmark($smarty, $local_templates_dir, $module_name);
}

function handleJSON_save_sticky_note($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('ERROR');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $description_note = getParameter("description");
        $popup_note = getParameter("popup");
        $output = saveStickyNote($id_menu, $description_note, $popup_note);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'OK' : 'ERROR');
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_get_sticky_note($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('ERROR');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        global $arrConf;

        $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
        $pACL = new paloACL($pdbACL);
        $idUser = $pACL->getIdUser($_SESSION['issabel_user']);

        $output = getStickyNote($pdbACL, $idUser, $id_menu);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'OK' : 'ERROR');
        $jsonObject->set_error($output['msg']);
        $jsonObject->set_message($output['data']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_readNoti($smarty, $local_templates_dir, $module_name)
{
    global $arrConf;
    $jsonObject = new PaloSantoJSON();
    $id_noti = getParameter("id_noti");
    if (!empty($id_noti)) {
        $pdbACL = new paloDB($arrConf['issabel_dsn']['acl']);
        $pNot = new paloNotification($pdbACL);
        $pNot->deleteNotification('', $id_noti);
    }
    return '';
}


function handleJSON_saveNeoToggleTab($smarty, $local_templates_dir, $module_name)
{
    $jsonObject = new PaloSantoJSON();
    $id_menu = getParameter("id_menu");
    if (empty($id_menu)) {
        $jsonObject->set_status('false');
        $jsonObject->set_error(_tr('Module not specified'));
    } else {
        $statusTab  = getParameter("statusTab");
        $output = saveNeoToggleTabByUser($id_menu, $statusTab);
        $jsonObject->set_status(($output['status'] === TRUE) ? 'true' : 'false');
        $jsonObject->set_error($output['msg']);
    }
    return $jsonObject->createJSON();
}

function handleJSON_showAboutUs($smarty, $local_templates_dir, $module_name)
{
    global $arrConf;

    $jsonObject = new PaloSantoJSON();
    $smarty->assign('ABOUT_ISSABEL_CONTENT', _tr('About Issabel Content'));
    $jsonObject->set_message(array(
        'title' =>  (in_array($arrConf['mainTheme'], array('elastixwave', 'elastixneo'))
                ? _tr('About Issabel2')
                : _tr('About Issabel') . " " . $arrConf['issabel_version']),
        'html'  =>  $smarty->fetch("$local_templates_dir/_aboutus.tpl"),
    ));
    return $jsonObject->createJSON();
}

function handleJSON_extension_current_user($smarty, $local_templates_dir, $module_name)
{
    global $pACL;
    $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
    $extension = $pACL->getUserExtension($user);

    $msg = array(
        'tech'              =>  NULL,
        'authorizationUser' =>  NULL,
        'password'          =>  NULL,
    );
    $jsonObject = new PaloSantoJSON();
    $jsonObject->set_status('ERROR');
    if (!is_numeric($extension)) {
        $jsonObject->set_error(_tr('Extension not set!'));
    } else {
        // esto asume account==extension en FreePBX
        $msg['authorizationUser'] = $extension;

        // Leer tecnología y password de FreePBX
        $pDB = new paloDB(generarDSNSistema('asteriskuser', 'asterisk'));
        if ($pDB->errMsg != '') {
            $jsonObject->set_error($pDB->errMsg);
        } else {
            $tupla = $pDB->getFirstRowQuery('SELECT id, tech, description FROM devices WHERE id = ? AND devicetype = "fixed"',
                TRUE, array($extension));
            if (!is_array($tupla)) {
                $jsonObject->set_error($pDB->errMsg);
            } elseif (count($tupla) <= 0) {
                $jsonObject->set_error(_tr('Extension not set or not found'));
            } elseif (!in_array($tupla['tech'], array('sip', 'iax2'))) {
                $jsonObject->set_error(_tr('Unsupported technology'));
            } else {
                $msg['tech'] = $tupla['tech'];
                $msg['displayName'] = $tupla['description'];

                $techtable = array('sip' => 'sip', 'iax2' => 'iax');
                $tupla = $pDB->getFirstRowQuery('SELECT data FROM '.$techtable[$msg['tech']].' WHERE id = ? AND keyword = "secret"',
                    TRUE, array($extension));
                if (!is_array($tupla)) {
                    $jsonObject->set_error($pDB->errMsg);
                } elseif (count($tupla) <= 0) {
                    $jsonObject->set_error(_tr('Extension not set or not found'));
                } else {
                    $msg['password'] = $tupla['data'];
                    $jsonObject->set_status('OK');
                }
            }
        }
    }

    $jsonObject->set_message($msg);
    return $jsonObject->createJSON();
}
?>
