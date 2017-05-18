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
 $Id: index.php,v 1.1.1.1 2007/07/06 21:31:56 alex Exp $ */

require_once('libs/paloSantoDB.class.php');
require_once('libs/paloSantoACL.class.php');

function _moduleContent(&$smarty, $module_name)
{
    require_once("modules/$module_name/configs/default.conf.php");
    require_once("modules/$module_name/libs/paloSantoUserPluginBase.class.php");

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf, $arrConfModule);

    // folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pDB = new paloDB($arrConf['elastix_dsn']['acl']);
    if (!empty($pDB->errMsg)) {
        return "ERROR DE DB: $pDB->errMsg <br>";
    }
    $pACL = new paloACL($pDB);
    if (!empty($pACL->errMsg)) {
        return "ERROR DE ACL: $pACL->errMsg <br>";
    }

    // Enumerar los plugines de datos de usuario
    $plugins = array();
    foreach (scandir("modules/$module_name/plugins") as $p) {
        if (!in_array($p, array('.', '..')) && is_dir("modules/$module_name/plugins/$p") &&
            file_exists("modules/$module_name/plugins/$p/index.php")) {
            if (file_exists("modules/$module_name/plugins/$p/lang/en.lang"))
                load_language_module("$module_name/plugins/$p");
            $plugins['paloUserPlugin_'.$p] = "modules/$module_name/plugins/$p/index.php";
        }
    }

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';
    switch ($action) {
    case 'new':
        return createUser($pACL, $smarty, $module_name, $local_templates_dir, $plugins);
    case 'edit':
        return editUser($pACL, $smarty, $module_name, $local_templates_dir, $plugins);
    case 'edit_userExtension': // <-- editar info de usuario logoneado en ventana independiente
        return editUser_userExtension($pACL, $smarty, $module_name, $local_templates_dir, $plugins);
    case 'list':
    default:
        return listUsers($pACL, $smarty, $module_name, $local_templates_dir, $plugins);
    }
}

function listUsers($pACL, $smarty, $module_name, $local_templates_dir, $plugins)
{
    require_once('libs/paloSantoGrid.class.php');

    $id_user_session = $pACL->getIdUser($_SESSION['elastix_user']);

    /* Un usuario en el grupo de administradores puede ver la lista de todos los
     * usuarios en el sistema. Cualquier otro usuario sólo puede ver su propia
     * información. */
    $bViewAllUsers = hasModulePrivilege($_SESSION['elastix_user'], $module_name, 'viewany');

    /* Un usuario en el grupo de administradores puede editar cualquier usuario
     * del sistema. Cualquier otro usuario sólo puede editar su propia
     * información. */
    $bEditAnyUser = hasModulePrivilege($_SESSION['elastix_user'], $module_name, 'editany');

    /* Un usuario en el grupo de administradores puede borrar cualquier usuario
     * del sistema distinto al propio. Cualquier otro usuario está negado de
     * borrar su usuario. */
    $bDeleteAnyUser = hasModulePrivilege($_SESSION['elastix_user'], $module_name, 'deleteany');

    // Ejecutar borrado de usuario si está autorizado
    if (isset($_POST['delete']) && isset($_POST['id_user'])) {
        if ($id_user_session == $_POST['id_user']) {
            $smarty->assign(array(
                'mb_title'      =>  _tr('ERROR'),
                'mb_message'    =>  _tr('Cannot delete current session user'),
            ));
        } elseif (!$bDeleteAnyUser) {
            $smarty->assign(array(
                'mb_title'      =>  _tr('ERROR'),
                'mb_message'    =>  _tr('userNoAllowed'),
            ));
        } else if (!$pACL->deleteUser($_POST['id_user'])) {
            $smarty->assign(array(
                'mb_title'      =>  _tr('ERROR'),
                'mb_message'    =>  _tr('(internal) Failed to delete user').': '.$pACL->errMsg,
            ));
        }
    }

    // Instanciar los objetos de plugines
    $pobj = array();
    foreach ($plugins as $classname => $classfile) {
        require_once $classfile;
        $pobj[$classname] = new $classname($pACL);
    }

    $limit  = 20;
    $total = $bViewAllUsers ? $pACL->getNumUsers() : 1;
    $total = ($total == NULL) ? 0 : $total;

    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $oGrid->pagingShow(true);
    $oGrid->setTitle(_tr("User List"));
    $oGrid->setIcon("images/user.png");
    $oGrid->setURL(array('menu' => $module_name));
    $offset = $oGrid->calculateOffset();

    // Columnas para reporte base y todos los plugins
    $arrColumns = array('', _tr("Login"), _tr("Real Name"), _tr("Group"));
    foreach ($pobj as $p) $arrColumns = array_merge($arrColumns, $p->userReport_labels());
    $oGrid->setColumns($arrColumns);

    if ($bViewAllUsers) {
        $arrUsers = $pACL->getUsersPaging($limit, $offset);
    } else {
        $arrUsers = $pACL->getUsers($id_user_session);
    }

    $arrData = array();
    foreach ($arrUsers as $user) {
        $arrMembership  = $pACL->getMembership($user[0]);

        // FIXME: es correcto ucfirst() con internacionalización no-latin?
        $grouplist = is_array($arrMembership) ? implode(' ', array_map('ucfirst', array_map('_tr', array_keys($arrMembership)))) : '';
        $arrTmp = array(
            "<input type=\"radio\" name=\"id_user\" value=\"{$user[0]}\" ".(($bDeleteAnyUser && $user[0] != $id_user_session) ? '' : ' disabled="disabled"')." />",
            ($bEditAnyUser || $user[1] == $_SESSION['elastix_user'])
                ? "<a href='?menu={$module_name}&action=edit&id_user=".$user[0]."'>".htmlentities($user[1], ENT_COMPAT, 'UTF-8')."</a>"
                : htmlentities($user[1], ENT_COMPAT, 'UTF-8'),
            htmlentities($user[2], ENT_COMPAT, 'UTF-8'),
            htmlentities($grouplist, ENT_COMPAT, 'UTF-8'),
        );

        foreach ($pobj as $p) $arrTmp = array_merge($arrTmp, $p->userReport_data($user[1], $user[0]));

        $arrData[] = $arrTmp;
    }
    $oGrid->setData($arrData);

    /* Un usuario en el grupo de administradores puede crear nuevos usuarios. */
    if (hasModulePrivilege($_SESSION['elastix_user'], $module_name, 'create')) {
        $oGrid->addNew("?menu={$module_name}&action=new", _tr('Create New User'), TRUE);
    }
    if ($bDeleteAnyUser) {
        $oGrid->deleteList('Are you sure you want to delete this user?', 'delete', _tr('Delete User'));
    }
    return $oGrid->fetchGrid();
}

function createUser($pACL, $smarty, $module_name, $local_templates_dir, $plugins)
{
    if (!hasModulePrivilege($_SESSION['elastix_user'], $module_name, 'create')) {
        $smarty->assign(array(
            'mb_title'      =>  _tr('ERROR'),
            'mb_message'    =>  _tr('userNoAllowed'),
        ));
        return listUsers($smarty, $module_name, $local_templates_dir);
    }
    return createEditUser($pACL, $smarty, $module_name, $local_templates_dir, NULL, TRUE, $plugins);
}

function editUser($pACL, $smarty, $module_name, $local_templates_dir, $plugins)
{
    $id_user = (isset($_REQUEST['id_user']) && ctype_digit($_REQUEST['id_user'])) ? (int)$_REQUEST['id_user'] : NULL;
    if (is_null($id_user)) {
        Header('Location: ?menu='.$module_name);
        return '';
    }

    $id_user_session = $pACL->getIdUser($_SESSION['elastix_user']);
    $privileged = hasModulePrivilege($_SESSION['elastix_user'], $module_name, 'editany');
    if ($id_user != $id_user_session && !$privileged) {
        $smarty->assign(array(
            'mb_title'      =>  _tr('ERROR'),
            'mb_message'    =>  _tr('userNoAllowed'),
        ));
        return listUsers($pACL, $smarty, $module_name, $local_templates_dir);
    }
    return createEditUser($pACL, $smarty, $module_name, $local_templates_dir, $id_user, $privileged, $plugins);
}

function editUser_userExtension($pACL, $smarty, $module_name, $local_templates_dir, $plugins)
{
    global $arrConf;

    $_REQUEST['id_user'] = $pACL->getIdUser($_SESSION['elastix_user']); // HACK
    $smarty->assign('editUserExtension', 'yes');
    $c = editUser($pACL, $smarty, $module_name, $local_templates_dir, $plugins);
    if (isset($_REQUEST['rawmode']) && $_REQUEST['rawmode'] == 'yes') {
        $smarty->assign(array(
            'CONTENT'   =>  $c,
            'THEMENAME' =>  $arrConf['mainTheme'],
            'MODULE_NAME'   =>  $module_name,
        ));
        $c = $smarty->display("$local_templates_dir/edit_userExtension.tpl");
    }
    return $c;
}

function createEditUser($pACL, $smarty, $module_name, $local_templates_dir, $id_user,
    $privileged, $plugins)
{
    require_once("libs/paloSantoForm.class.php");

    $smarty->assign(array(
        "SAVE"              =>  _tr("Save"),
        "CANCEL"            =>  _tr("Cancel"),
        "REQUIRED_FIELD"    =>  _tr("Required field"),
        'LBL_CORE_FIELDS'   =>  _tr('Main Fields'),
        "icon"              =>  "images/user.png",
        'module_name'       =>  $module_name,
    ));

    // Instanciar los objetos de plugines
    $pobj = array();
    foreach ($plugins as $classname => $classfile) {
        require_once $classfile;
        $pobj[$classname] = new $classname($pACL);
    }

    /* El usuario privilegiado puede asignar cualquier grupo. El no privilegiado
     * está restringido a los grupos a los cuales pertenece. */
    $arrGruposACL = $pACL->getGroups();
    $arrMembership = is_null($id_user) ? NULL : $pACL->getMembership($id_user);
    $arrGrupos = array();
    foreach ($arrGruposACL as $groupinfo) {
        if ($privileged || is_null($arrMembership) || in_array($groupinfo[0], $arrMembership)) {
            $arrGrupos[$groupinfo[0]] = ucfirst(_tr($groupinfo[1]));
        }
    }

    $arrFormElements = createFormFields($arrGrupos);
    if (!is_null($id_user)) {
        $arrUser = $pACL->getUsers($id_user);
        if (count($arrUser) <= 0) {
            Header('Location: ?menu='.$module_name);
            return '';
        }
        $smarty->assign('id_user', $id_user);
        $gids = array_values($arrMembership);
        $userinfo = array(
            'name'          =>  $arrUser[0][1],
            'description'   =>  $arrUser[0][2],
            'group'         =>  $gids[0],   // Se asume un solo grupo por usuario
            'password1'     =>  '********',
            'password2'     =>  '********',
        );
        foreach ($userinfo as $k => $v) if (!isset($_POST[$k])) $_POST[$k] = $v;
        $arrFormElements['password1']['REQUIRED'] = 'no';
        $arrFormElements['password2']['REQUIRED'] = 'no';

        foreach ($pobj as $p) $p->loadFormEditValues($userinfo['name'], $id_user);
    }

    // Colocar aquí elementos adicionales en $arrFormElements para plugins
    foreach ($pobj as $p) $arrFormElements = array_merge($arrFormElements,
        $p->addFormElements($privileged));

    $oForm = new paloForm($smarty, $arrFormElements);
    if (!is_null($id_user)) $oForm->setEditMode();

    if (isset($_POST['save'])) {
        if (!$oForm->validateForm($_POST)) {
            $smarty->assign(array(
                "mb_title"  =>  _tr("Validation Error"),
                "mb_message"=>  "<b>"._tr('The following fields contain errors').":</b><br/>".
                    implode(', ', array_keys($oForm->arrErroresValidacion)),
            ));
        } elseif ((is_null($id_user) && empty($_POST['password1'])) ||
            ($_POST['password1'] != $_POST['password2'])) {
            $smarty->assign(array(
                "mb_title"  =>  _tr("Validation Error"),
                "mb_message"=>  _tr("The passwords are empty or don't match"),
            ));
        } else {
            $bPluginError = FALSE;

            // TODO: beginTransaction
            if (is_null($id_user)) {
                $r = $pACL->createUser($_POST['name'], $_POST['description'],
                    md5($_POST['password1']));
                if ($r) $id_user = $pACL->getIdUser($_POST['name']);
                if (!is_null($id_user)) {
                    /* Versiones viejas del archivo acl.db tienen una fila con
                     * una tupla que asocia al usuario inexistente con ID 2, con
                     * el grupo 2 (Operadores). Se limpia cualquier membresía
                     * extraña. */
                    $listaMembresia = $pACL->getMembership($id_user);
                    if (is_array($listaMembresia) && count($listaMembresia) > 0) {
                        foreach ($listaMembresia as $idGrupo) {
                            $pACL->delFromGroup($id_user, $idGrupo);
                        }
                    }

                    // Creo la membresia
                    $r = $pACL->addToGroup($id_user, $_POST['group']);

                    // Operaciones de plugines para nuevo usuario
                    if ($r) {
                        foreach ($pobj as $p) {
                            if (!$p->runPostCreateUser($smarty, $_POST['name'], $id_user)) {
                                $r = FALSE;
                                $bPluginError = TRUE;
                                break;
                            }
                        }
                    }
                }
            } else {
                $r = $pACL->updateUser($id_user, $userinfo['name'], $_POST['description']);
                if ($privileged && $userinfo['group'] != $_POST['group']) {
                    if ($r) $r = $pACL->delFromGroup($id_user, $userinfo['group']);
                    if ($r) $r = $pACL->addToGroup($id_user, $_POST['group']);
                }
                if ((!empty($_POST['password1'])) && ($_POST['password1'] != '********')) {
                    $md5pass = md5($_POST['password1']);
                    if ($r) $r = $pACL->changePassword($id_user, $md5pass);
                    if ($r && $pACL->getIdUser($_SESSION['elastix_user']) == $id_user) {
                        // Cambio de clave del propio usuario debe actualizar sesión
                        $_SESSION['elastix_pass'] = $md5pass;
                    }
                }

                if ($r) {
                    // Operaciones de plugines para usuario modificado
                    foreach ($pobj as $p) {
                        if (!$p->runPostUpdateUser($smarty, $userinfo['name'], $id_user, $privileged)) {
                            $r = FALSE;
                            $bPluginError = TRUE;
                            break;
                        }
                    }
                }
            }
            if (!$r) {
                if (!$bPluginError) $smarty->assign(array(
                    'mb_title'  =>  'ERROR',
                    'mb_message'=>  $pACL->errMsg,
                ));
                // TODO: rollback
            } else {
                // TODO: commit
                if ($_REQUEST['action'] == 'edit_userExtension') {
                    $smarty->assign('userExtension_success', 1);
                } else {
                    Header('Location: ?menu='.$module_name);
                    return '';
                }
            }
        }
    }

    $plug_content = '';
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    foreach ($pobj as $classname => $p) {
        $plugin_templates_dir = $base_dir.'/'.str_replace('index.php', 'tpl', $plugins[$classname]);
        $plug_content .= $p->fetchForm($smarty, $oForm, $plugin_templates_dir, $_POST);
    }
    $smarty->assign('PLUGIN_CONTENT', $plug_content);

    return $oForm->fetchForm("$local_templates_dir/new.tpl",
        is_null($id_user) ? _tr('New User') : _tr('Edit User').' "'.$userinfo['name'].'"',
        $_POST);
}

function createFormFields($arrGrupos)
{
    return array(
        "description" => array(
            "LABEL"                  => ""._tr('Name')." "._tr('(Ex. John Doe)')."",
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""),
        "name"       => array(
            "LABEL"                   => _tr("Login"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "TEXT",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => "",
            "EDITABLE"               => "no"),
        "password1"   => array(
            "LABEL"                  => _tr("Password"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "PASSWORD",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""),
        "password2"   => array(
            "LABEL"                  => _tr("Retype password"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "PASSWORD",
            "INPUT_EXTRA_PARAM"      => "",
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""),
         "group"       => array(
            "LABEL"                  => _tr("Group"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrGrupos,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""),
    );
}

// Abstracción de privilegio por módulo hasta implementar (Elastix bug #1100).
// Parámetro $module se usará en un futuro al implementar paloACL::hasModulePrivilege().
function hasModulePrivilege($user, $module, $privilege)
{
    global $arrConf;

    $pDB = new paloDB($arrConf['elastix_dsn']['acl']);
    $pACL = new paloACL($pDB);

    if (method_exists($pACL, 'hasModulePrivilege'))
        return $pACL->hasModulePrivilege($user, $module, $privilege);

    $isAdmin = ($pACL->isUserAdministratorGroup($user) !== FALSE);
    return ($isAdmin && in_array($privilege, array(
        'viewany',  // ¿Está autorizado el usuario a ver la información de todos los demás?
        'create',   // ¿Está autorizado el usuario a crear nuevos usuarios?
        'editany',  // ¿Está autorizado el usuario a modificar la información de otro usuario?
        'deleteany',// ¿Está autorizado el usuario a borrar otro usuario?
    )));
}
