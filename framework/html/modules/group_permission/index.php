<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.5.2                                                |
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
*/
require_once "libs/paloSantoGrid.class.php";
require_once "libs/paloSantoForm.class.php";
require_once "libs/paloSantoDB.class.php";
require_once "libs/paloSantoMenu.class.php";
require_once "libs/paloSantoNavigation.class.php";

function _moduleContent(&$smarty, $module_name)
{
    require_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf, $arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir']:'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';
    switch ($action) {
    case 'assigncustom':
        return assignCustomPrivileges($smarty, $module_name, $local_templates_dir);
    case 'list':
    default:
        return reportGroupPermission($smarty, $module_name, $local_templates_dir);
    }
}

class paloSantoNavGrid extends paloSantoNavigationBase
{
    function buildGrid($pACL, $customurlprefix, $admin)
    {
        $grid = array();
        $this->_buildGridChildren($pACL, $customurlprefix, $this->_menubase, $grid, 0, NULL, $admin);
        return $grid;
    }

    private function _buildGridChildren($pACL, $customurlprefix, $children, &$grid, $nlevel, $idparent, $admin)
    {
        $total = $enabled = 0;

        $buttontag = '<button class="resource-branch-manip level-'.($nlevel+1).'">'.
            '&nbsp;</button>';
        $idparenttag = is_null($idparent)
            ? ''
            : '<input type="hidden" name="idparent" value="'.$idparent.'" />';
        foreach (array_keys($children) as $key) {
            $idcheck = 'resource-access-'.$key;
            $disabledattr = ($admin && in_array($key, array('usermgr', 'grouplist', 'userlist', 'group_permission')))
                ? 'disabled="disabled"' : '';
            $checktag = '<input '.
                'type="checkbox" '.
                'id="'.$idcheck.'" '.
                'name="resource_access[]" '.
                'value="'.$children[$key]['id'].'" '.
                $disabledattr.
                (in_array('access', $children[$key]['actions']) ? 'checked' : '').' />'.
                '<label for="'.$idcheck.'">&nbsp;</label>';
            $idtag = $idparenttag.
                '<input type="hidden" name="id" value="'.$children[$key]['id'].'"/>';
            $tupla = array(
                '&nbsp;',
                '&nbsp;',
                '&nbsp;',
                htmlspecialchars($key, ENT_COMPAT, 'UTF-8'),
                htmlspecialchars(_tr($children[$key]['Name']), ENT_COMPAT, 'UTF-8'),
                '&nbsp;',
                '&nbsp;',
                '&nbsp;',
            );
            $tupla[$nlevel] = $idtag.(($children[$key]['HasChild']) ? $buttontag : $checktag);

            $curpos = count($grid);
            $grid[] = $tupla;

            // Si este no es un nodo final, se actualiza la cuenta de total y activos
            if ($children[$key]['HasChild']) {
                list($childrentotal, $childrenenabled) = $this->_buildGridChildren(
                    $pACL, $customurlprefix, $children[$key]['children'], $grid, $nlevel + 1, $children[$key]['id'], $admin);
                $grid[$curpos][5] = '<b>'.$childrentotal.'</b>';
                $grid[$curpos][6] = '<span '.(($childrenenabled > 0) ? 'style="font-weight: bold;"' : '').' class="enabledcount">'.$childrenenabled."</span>";
                $total += $childrentotal;
                $enabled += $childrenenabled;
            } else {
                $total++;
                if (in_array('access', $children[$key]['actions'])) $enabled++;

                $privlist = $pACL->getModulePrivileges($key);
                if (is_array($privlist) && count($privlist) > 0) {
                    $grid[$curpos][7] = '<a href="'.$customurlprefix.'&amp;resource='.$key.'" >['._tr('Custom privileges').']</a>';
                }
            }
        }

        return array($total, $enabled);
    }
    /**
     * Este procedimiento normaliza la activación y desactivación de acciones
     * de acuerdo a las siguientes reglas:
     * - Un nodo de primer nivel no tiene seteadas acciones, a menos que no
     *   tenga hijos. De otro modo obedece las reglas siguientes.
     * - Un nodo que no tiene hijos puede tener la acción seteada o ausente.
     * - Un nodo que tiene hijos DEBE tener la acción seteada si al menos un
     *   hijo en cualquier nivel sucesivo la tiene, y NO DEBE tener la acción
     *   seteada si ninguno de los hijos la tiene.
     *
     * @param string $k     Acción a normalizar
     */
    function normalizeActions($k)
    {
        $this->_normalizeChildrenWithAction($this->_menubase, $k);
        foreach (array_keys($this->_menubase) as $nkey) {
            if ($this->_menubase[$nkey]['HasChild'])
              $this->_menubase[$nkey]['actions'] = array();
        }
    }

    // Normaliza en todos los hijos y devuelve número de hijos con acción
    private function _normalizeChildrenWithAction(&$children, $k)
    {
        $action_count = 0;
        foreach (array_keys($children) as $nkey) {
            if ($children[$nkey]['HasChild']) {
                // Hay hijos. Se anula la acción y se setea de nuevo según los hijos
                $children[$nkey]['actions'] = array_diff($children[$nkey]['actions'], array($k));
                $child_count = $this->_normalizeChildrenWithAction($children[$nkey]['children'], $k);
                if ($child_count > 0) $children[$nkey]['actions'][] = $k;
                $action_count += $child_count;
            } else {
                // No hay hijos, se suma 1 si tiene la acción
                if (in_array($k, $children[$nkey]['actions']))
                    $action_count++;
            }
        }
        return $action_count;
    }

    // Extraer todos los módulos que tienen seteada la acción
    function listModulesWithAction($k)
    {
        $modlist = array();
        foreach ($this->_menunodes as $nkey => &$node) {
            if (in_array($k, $node['actions'])) $modlist[] = $nkey;
        }
        return $modlist;
    }
}

function reportGroupPermission($smarty, $module_name, $local_templates_dir)
{
    global $arrConf;
    global $pACL;

    $smarty->assign("SHOW", _tr("Show"));

    // Seleccionar grupo a asignar permisos
    $id_admin = 1;
    $id_group = getParameter('filter_group');
    if (empty($id_group)) $id_group = $id_admin;
    $_POST['filter_group'] = $id_group;

    // ¿Es este grupo el grupo administrador?
    $bIsAdminGroup = ($id_group == 1);

    // Cargar el menú completo
    $oMenu = new paloMenu($arrConf['elastix_dsn']['menu']);
    $fullmenu = $oMenu->cargar_menu();
    foreach (array_keys($fullmenu) as $k) $fullmenu[$k]['actions'] = array();

    if (isset($_POST['apply']) && isset($_POST['resource_access']) && is_array($_POST['resource_access'])) {
        applyNewGroupPermissions($smarty, $id_group, $fullmenu, $bIsAdminGroup);
    }

    // Cargar permisos de módulos
    $mod_permissions = $pACL->loadGroupPermissions($id_group);
    foreach ($mod_permissions as $tupla) {
        if (isset($fullmenu[$tupla['resource_name']]) &&
            !in_array($tupla['action_name'], $fullmenu[$tupla['resource_name']]['actions'])) {
            $fullmenu[$tupla['resource_name']]['actions'][] = $tupla['action_name'];
        }
    }

    // Organizar el árbol de módulos
    $oArbol = new paloSantoNavGrid($fullmenu);

    // Selección de grupo a modificar
    $arrFilterForm = createFieldFilter();
    $sNombreGrupo = $arrFilterForm["filter_group"]["INPUT_EXTRA_PARAM"][$id_group];
    $oFilterForm = new paloForm($smarty, $arrFilterForm);

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->pagingShow(FALSE);
    $oGrid->setColumns(array('', '', '', _tr('Resource'), _tr('Description'),
        _tr('Available'), _tr('Enabled'), _tr('Custom Privileges')));
    $oGrid->setData($oArbol->buildGrid($pACL, '?menu='.$module_name.'&amp;action=assigncustom&amp;id_group='.$id_group, $bIsAdminGroup));
    $oGrid->addSubmitAction('apply', _tr('Apply'));
    $oGrid->setIcon('images/list.png');
    $oGrid->setTitle(_tr("Group Permission"));
    $oGrid->showFilter(trim($oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST)));
    $oGrid->addFilterControl(_tr("Filter applied ")._tr("Group")." = $sNombreGrupo",
        $_POST, array("filter_group" => 1), true);

    return $oGrid->fetchGrid();
}

function createFieldFilter()
{
    global $pACL;

    $arrGruposACL = $pACL->getGroups();
    $arrGrupos = array();

    $gruposTrad = array('administrator', 'operator', 'extension');
    for ($i = 0; $i < count($arrGruposACL); $i++ ) {
        $arrGrupos[$arrGruposACL[$i][0]] = in_array($arrGruposACL[$i][1], $gruposTrad)
            ? _tr($arrGruposACL[$i][1]) : $arrGruposACL[$i][1];
    }
    return array(
        "filter_group" => array(
            "LABEL"                     => _tr("Group"),
            "REQUIRED"                  => "no",
            "INPUT_TYPE"                => "SELECT",
            "INPUT_EXTRA_PARAM"         => $arrGrupos,
            "VALIDATION_TYPE"           => "text",
            "VALIDATION_EXTRA_PARAM"    => ""
        ),
    );
}

function applyNewGroupPermissions($smarty, $id_group, $fullmenu, $is_admin)
{
    global $pACL;

    if ($is_admin) {
        $_POST['resource_access'] = array_unique(array_merge(
            $_POST['resource_access'],
            array('usermgr', 'grouplist', 'userlist', 'group_permission')));
    }
    foreach ($_POST['resource_access'] as $resource_name) {
        if (isset($fullmenu[$resource_name]) &&
            !in_array('access', $fullmenu[$resource_name]['actions'])) {
            $fullmenu[$resource_name]['actions'][] = 'access';
        }
    }

    // Conjunto anterior de módulos con permiso
    $modules_action_old = array();
    $mod_permissions = $pACL->loadGroupPermissions($id_group);
    foreach ($mod_permissions as $tupla) {
        if ($tupla['action_name'] == 'access')
            $modules_action_old[] = $tupla['resource_name'];
    }
    sort($modules_action_old);

    // Organizar el árbol de módulos con los nuevos permisos
    $oArbol = new paloSantoNavGrid($fullmenu);
    $modules_action_new = $oArbol->listModulesWithAction('access');
    sort($modules_action_new);
    $oArbol->normalizeActions('access');
    $modules_action_normalized = $oArbol->listModulesWithAction('access');
    sort($modules_action_normalized);
    $modules_toremove = array_diff($modules_action_old, $modules_action_normalized);
    $modules_toadd = array_diff($modules_action_normalized, $modules_action_old);
/*
    file_put_contents('/tmp/debug_gperm.txt', "Permisos viejos    : ".implode(' ', $modules_action_old)."\n\n", FILE_APPEND);
    file_put_contents('/tmp/debug_gperm.txt', "Permisos nuevos (1): ".implode(' ', $modules_action_new)."\n\n", FILE_APPEND);
    file_put_contents('/tmp/debug_gperm.txt', "Permisos nuevos (2): ".implode(' ', $modules_action_normalized)."\n\n", FILE_APPEND);
    file_put_contents('/tmp/debug_gperm.txt', "Permisos implícitos: ".implode(' ', array_diff($modules_action_normalized, $modules_action_new))."\n\n", FILE_APPEND);
    file_put_contents('/tmp/debug_gperm.txt', "Permisos a quitar  : ".implode(' ', $modules_toremove)."\n\n", FILE_APPEND);
    file_put_contents('/tmp/debug_gperm.txt', "Permisos a agregar : ".implode(' ', $modules_toadd)."\n\n", FILE_APPEND);
*/
    // Cargar IDs de los recursos
    $nres = $pACL->getNumResources('');
    if ($nres == 0) {
        $smarty->assign(array(
            'mb_title'    =>  _tr('Cannot count resources'),
            'mb_message'  =>  $pACL->errMsg,
        ));
        return;
    }
    $list_resources = $pACL->getListResources($nres, 0, '');
    if (count($list_resources) <= 0) {
        $smarty->assign(array(
            'mb_title'    =>  _tr('Cannot load resource IDs'),
            'mb_message'  =>  $pACL->errMsg,
        ));
        return;
    }
    $resource_ids = array();
    foreach ($list_resources as $tupla) {
        $resource_ids[$tupla['name']] = $tupla['id'];
    }

    // Traducir de nombres a IDs
    $modules_id_toadd = array(); $modules_id_toremove = array();
    foreach ($modules_toadd as $r) $modules_id_toadd[] = $resource_ids[$r];
    foreach ($modules_toremove as $r) $modules_id_toremove[] = $resource_ids[$r];

    if (count($modules_id_toadd) > 0) {
        if (!$pACL->saveGroupPermissions('access', $id_group, $modules_id_toadd)) {
            $smarty->assign(array(
                'mb_title'    =>  _tr('Cannot add enabled resources'),
                'mb_message'  =>  $pACL->errMsg,
            ));
            return;
        }
    }

    if (count($modules_id_toremove) > 0) {
        if (!$pACL->deleteGroupPermissions('access', $id_group, $modules_id_toremove)) {
            $smarty->assign(array(
                'mb_title'    =>  _tr('Cannot remove disabled resources'),
                'mb_message'  =>  $pACL->errMsg,
            ));
            return;
        }
    }
}

function assignCustomPrivileges($smarty, $module_name, $local_templates_dir)
{
    global $arrConf;
    global $pACL;

    if (!isset($_REQUEST['id_group']) || !isset($_REQUEST['resource'])) {
        Header('Location: ?menu='.$module_name);
        return;
    }

    // Privilegios disponibles para el módulo
    $privlist = $pACL->getModulePrivileges($_REQUEST['resource']);
    if (!is_array($privlist) || count($privlist) <= 0) {
        Header('Location: ?menu='.$module_name);
        return;
    }

    // Conjunto de privilegios asignados al grupo para este módulo
    $id_resource = $pACL->getIdResource($_REQUEST['resource']);
    $currpriv = $pACL->getCurrentModulePrivilegesGroup(
        $id_resource, $_REQUEST['id_group']);

    // Guardar nuevo conjunto de privilegios
    if (isset($_POST['apply'])) {
        $bExito = TRUE;
        $newpriv = array();
        if (isset($_POST['privileges']) && is_array($_POST['privileges']))
            $newpriv = $_POST['privileges'];
        foreach ($privlist as $tupla) {
            if (isset($currpriv[$tupla['id']]) && !in_array($tupla['id'], $newpriv)) {
                // Privilegio a revocar
                $bExito = $pACL->revokeModulePrivilege2Group($tupla['id'], $_REQUEST['id_group']);
            } elseif (!isset($currpriv[$tupla['id']]) && in_array($tupla['id'], $newpriv)) {
                // Privilegio a otorgar
                $bExito = $pACL->grantModulePrivilege2Group($tupla['id'], $_REQUEST['id_group']);
            }
            if (!$bExito) break;
        }
        if (!$bExito) {
            $smarty->assign(array(
                'mb_title'    =>  _tr('Cannot update custom privileges'),
                'mb_message'  =>  $pACL->errMsg,
            ));
        } else {
            Header('Location: ?menu='.$module_name.'&filter_group='.$_REQUEST['id_group']);
            return;
        }
    }

    $arrData = array();
    foreach ($privlist as $tupla) {
        $idcheck = 'resource-privilege-'.$tupla['privilege'];
        $arrData[] = array(
            '<input type="checkbox" name="privileges[]" id="'.$idcheck.'" value="'.$tupla['id'].'" '.
                (isset($currpriv[$tupla['id']]) ? 'checked' : '').' /><label for="'.$idcheck.'">&nbsp;</label>',
            htmlentities($tupla['privilege'], ENT_COMPAT, 'UTF-8'),
            htmlentities($tupla['desc_privilege'], ENT_COMPAT, 'UTF-8'),
        );
    }

    $rs = $pACL->getResources($id_resource);
    $rsname = _tr($rs[0][2]);
    $gname = $pACL->getGroupNameByid($_REQUEST['id_group']);
    $title = _tr('Custom privileges for {1} on {2}');
    $title = str_replace('{1}', $gname, $title);
    $title = str_replace('{2}', $rsname, $title);

    $oGrid = new paloSantoGrid($smarty);
    $oGrid->pagingShow(FALSE);
    $oGrid->setColumns(array('', _tr('Privilege'), _tr('Description')));
    $oGrid->setData($arrData);
    $oGrid->addSubmitAction('apply', _tr('Save'));
    $oGrid->setIcon('images/list.png');
    $oGrid->setTitle($title);

    return $oGrid->fetchGrid();
}
