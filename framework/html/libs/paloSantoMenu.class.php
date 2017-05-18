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
  $Id: paloSantoMenu.class.php,v 1.2 2007/09/05 00:25:25 gcarrillo Exp $ */

if (isset($arrConf['basePath'])) {
    include_once($arrConf['basePath'] . "/libs/paloSantoDB.class.php");
} else {
    include_once("libs/paloSantoDB.class.php");
}

class paloMenu {

    var $_DB; // instancia de la clase paloDB
    var $errMsg;

    function paloMenu(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    function cargar_menu()
    {
       //leer el contenido de la tabla menu y devolver un arreglo con la estructura
        $menu = array ();
        $query="Select m1.*, (Select count(*) from menu m2 where m2.IdParent=m1.id) as HasChild from menu m1 order by order_no asc;";
        $oRecordset = $this->_DB->fetchTable($query, true);
        if ($oRecordset){
            foreach($oRecordset as $key => $value)
            {
                if($value['HasChild']>0)
                    $value['HasChild'] = true;
                else $value['HasChild'] = false;
                $menu[$value['id']]= $value;
            }
        }
        return $menu;
    }

    function filterAuthorizedMenus($idUser)
    {
    	global $arrConf;

        $uelastix = FALSE;
        if (isset($_SESSION)) {
            $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
            if (empty($pDB->errMsg)) {
                $uelastix = get_key_settings($pDB, 'uelastix');
                $uelastix = ((int)$uelastix != 0);
            }
            unset($pDB);
        }

        if ($uelastix && isset($_SESSION['elastix_user_permission']))
            return $_SESSION['elastix_user_permission'];

        if (strpos($arrConf['elastix_dsn']['acl'], 'sqlite3:////') === 0) {
            // Adjuntar base de datos de ACL para acelerar búsqueda
            $bExito = $this->_DB->genQuery('ATTACH DATABASE ? AS acl',
                array(str_replace('sqlite3:////', '/', $arrConf['elastix_dsn']['acl'])));
            if (!$bExito) {
                $this->errMsg = $this->_DB->errMsg;
                return NULL;
            }
        }

        // Obtener todos los módulos autorizados
        $sPeticionSQL = <<<INFO_AUTH_MODULO
SELECT id, IdParent, Link, Name, Type, order_no
FROM menu, (
    SELECT acl_resource.name AS acl_resource_name, acl_group.name AS acl_name
    FROM acl_membership, acl_group, acl_group_permission, acl_resource
    WHERE acl_membership.id_user = ?
        AND acl_membership.id_group = acl_group.id
        AND acl_group.id = acl_group_permission.id_group
        AND acl_group_permission.id_resource = acl_resource.id
    UNION
    SELECT acl_resource.name AS acl_resource_name, acl_user.name AS acl_name
    FROM acl_user, acl_user_permission, acl_resource
    WHERE acl_user_permission.id_user = ?
        AND acl_user_permission.id_resource = acl_resource.id
) AS aclu
WHERE acl_resource_name = id ORDER BY order_no;
INFO_AUTH_MODULO;
        $arrMenuFiltered = array();
        $r = $this->_DB->fetchTable($sPeticionSQL, TRUE, array($idUser, $idUser));
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
        	return NULL;
        }
        if (strpos($arrConf['elastix_dsn']['acl'], 'sqlite3:////') === 0) {
            $this->_DB->genQuery('DETACH DATABASE acl');
        }
        foreach ($r as $tupla) {
        	$tupla['HasChild'] = FALSE;
            $arrMenuFiltered[$tupla['id']] = $tupla;
        }

        // Leer los menús de primer nivel
        $r = $this->_DB->fetchTable(
            'SELECT id, IdParent, Link, Name, Type, order_no, 1 AS HasChild '.
            'FROM menu WHERE IdParent = "" ORDER BY order_no', TRUE);
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        $menuPrimerNivel = array();
        foreach ($r as $tupla) {
            $tupla['HasChild'] = (bool)$tupla['HasChild'];
            $menuPrimerNivel[$tupla['id']] = $tupla;
        }

        // Resolver internamente las referencias de menú superior
        $menuSuperior = array();
        foreach (array_keys($arrMenuFiltered) as $k) {
        	$kp = $arrMenuFiltered[$k]['IdParent'];
            if (isset($arrMenuFiltered[$kp])) {
            	$arrMenuFiltered[$kp]['HasChild'] = TRUE;
            } elseif (isset($menuPrimerNivel[$kp])) {
                $menuSuperior[$kp] = $kp;
            } else {
                // Menú es de segundo nivel y no estaba autorizado
                unset($arrMenuFiltered[$k]);
            }
        }

        // Copiar al arreglo filtrado los menús de primer nivel EN EL ORDEN LEÍDO
        $arrMenuFiltered = array_merge(
            $arrMenuFiltered,
            array_intersect_key($menuPrimerNivel, $menuSuperior));

        if ($uelastix) $_SESSION['elastix_user_permission'] = $arrMenuFiltered;
        return $arrMenuFiltered;
    }

    /**
     * Procedimiento para obtener el listado de los menus.
     * MÉTODO DEPRECADO: sólo existe por compatibilidad con elastix-developer viejo.
     *
     * @return array    Listado de menus
     */
    function getRootMenus()
    {
        $this->errMsg = "";
        $listaMenus = array();
        $sQuery = "SELECT Id, Name FROM menu WHERE IdParent=''";
        $arrMenus = $this->_DB->fetchTable($sQuery);
        if (is_array($arrMenus)) {
           foreach ($arrMenus as $menu)
            {
                $listaMenus[$menu[0]]=$menu[1];
            }
        }else
        {
            $this->errMsg = $this->_DB->errMsg;
        }
        return $listaMenus;
    }

    private function _validateMenuParams($id, $name, $id_parent, $type, &$link)
    {
        if ($id == '' || $name == '') {
            $this->errMsg = "ID and module name cannot be empty";
            return FALSE;
        }
        if (!in_array($type, array('', 'module', 'framed'))) {
            $this->errMsg = "Invalid menuitem type";
            return FALSE;
        }
        if (($id_parent == '' && $type != '') || ($id_parent != '' && $type == '')) {
            $this->errMsg = "Conflict between menuitem type and first-level";
            return FALSE;
        }
        if ($type == 'framed') {
            if ($link == '') {
                $this->errMsg = "Link for framed menuitem cannot be empty";
                return FALSE;
            }
        } else {
            $link = '';
        }
        return TRUE;
    }

    /**
     * Crear un nuevo item de menú.
     *
     * @param string    $id         Nombre interno del módulo o nodo
     * @param string    $name       Texto a mostrar en GUI para el nodo
     * @param string    $id_parent  Nombre interno del nodo padre, o '' para primer nivel.
     * @param string    $type       Uno de '' 'module' 'framed'. El módulo de primer nivel SIEMPRE es ''.
     * @param string    $link       Para 'framed', el enlace a mostrar en el GUI en un <iframe>
     * @param string    $order      Número de orden de presentación del item
     *
     * @return bool     VERDADERO si el menu se crea correctamente, FALSO en error
     */
    function createMenu($id, $name, $id_parent, $type = 'module', $link = '', $order = -1)
    {
        if (!$this->_validateMenuParams($id, $name, $id_parent, $type, $link))
            return FALSE;

        // Verificación de existencia del menú
        $e = $this->existeMenu($id); if (is_null($e)) return FALSE;
        if ($e) {
            $this->errMsg = "Menu already exists";
            return FALSE;
        }

        // Verificación de existencia del padre
        if ($id_parent != '') {
            $e = $this->existeMenu($id_parent); if (is_null($e)) return FALSE;
            if (!$e) {
                $this->errMsg = "Requested parent does not exist";
                return FALSE;
            }
        }

        $sqlfields = array(
            'id'        =>  $id,
            'Name'      =>  $name,
            'Type'      =>  $type,
            'Link'      =>  $link,
            'IdParent'  =>  $id_parent,
        );
        if ($order != -1) $sqlfields['order_no'] = $order;
        $sql = 'INSERT INTO menu ('.
            implode(', ', array_keys($sqlfields)).') VALUES ('.
            implode(', ', array_fill(0, count($sqlfields), '?')).')';
        $r = $this->_DB->genQuery($sql, array_values($sqlfields));
        if (!$r) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Actualizar item de menú existente.
     *
     * @param string    $id         Nombre interno del módulo o nodo
     * @param string    $name       Texto a mostrar en GUI para el nodo
     * @param string    $id_parent  Nombre interno del nodo padre, o '' para primer nivel.
     * @param string    $type       Uno de '' 'module' 'framed'. El módulo de primer nivel SIEMPRE es ''.
     * @param string    $link       Para 'framed', el enlace a mostrar en el GUI en un <iframe>
     * @param string    $order      Número de orden de presentación del item
     *
     * @return bool     VERDADERO si el menu se crea correctamente, FALSO en error
     */
    function updateItemMenu($id, $name, $id_parent, $type = 'module', $link = '', $order = -1)
    {
        if (!$this->_validateMenuParams($id, $name, $id_parent, $type, $link))
        return FALSE;

        // Verificación de existencia del padre
        if ($id_parent != '') {
            $e = $this->existeMenu($id_parent); if (is_null($e)) return FALSE;
            if (!$e) {
                $this->errMsg = "Requested parent does not exist";
                return FALSE;
            }
        }

        $sql = 'UPDATE menu SET Name = ?, Type = ?, Link = ?, IdParent = ?';
        $params = array($name, $type, $link, $id_parent);
        if ($order != -1) {
            $sql .= ', order_no = ?';
            $params[] = $order;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $id;
        $r = $this->_DB->genQuery($sql, array_values($params));
        if (!$r) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para verificar si un item de menú existe por id.
     *
     * @param   string  $id_menu    El item a buscar.
     *
     * @return mixed    NULL en caso de error, o VERDADERO/FALSO.
     */
    function existeMenu($id_menu)
    {
        $sql = 'SELECT COUNT(*) AS N FROM menu WHERE id = ?';
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, array($id_menu));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        return ($tupla['N'] > 0);
    }

    /**
     * Delete the menu node from the menu database, as well as all its children.
     * If the just-deleted node was the last child of its parent, the parent is
     * also deleted.
     *
     * @param string    $menu_name   The name of the menu node
     * @param object    $acl   		 The class object ACL
     *
     * @return $menu_name   The menu which will be removed
     */

    function deleteMenu($menu_name, &$acl)
    {
        /* Climb up the menu tree as long as the examined item is the only child
         * node of its parent. */
        $sql_siblings = <<<SQL_SIBLINGS
SELECT COUNT(*) AS N, IdParent FROM menu
WHERE IdParent = (SELECT IdParent FROM menu WHERE id = ?)
GROUP BY IdParent
SQL_SIBLINGS;
        do {
            $tuple = $this->_DB->getFirstRowQuery($sql_siblings, TRUE, array($menu_name));
            if (!is_array($tuple)) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
            if (count($tuple) <= 0) {
                // Treat nonexistent menu node as success
                return TRUE;
            }
            $siblings = $tuple['N'];
            if ($siblings <= 1) $menu_name = $tuple['IdParent'];
        } while ($siblings <= 1);

        $nodesToRemove = array(
            array($menu_name, TRUE),
        );

        while (count($nodesToRemove) > 0) {
            $n = array_pop($nodesToRemove);
            if ($n[1]) {
                // Child nodes need to be loaded into delete list
                $rs = $this->_DB->fetchTable('SELECT id FROM menu where IdParent = ?', TRUE, array($n[0]));
                if (!is_array($rs)) {
                    $this->errMsg = $this->_DB->errMsg;
                    return FALSE;
                }
                array_push($nodesToRemove, array($n[0], FALSE));
                foreach ($rs as $tuple) array_push($nodesToRemove, array($tuple['id'], TRUE));
            } else {
                // Child nodes already deleted
                $id_resource = $acl->getIdResource($n[0]);
                if (!$acl->deleteIdResource($id_resource)) {
                    $this->errMsg = $acl->errMsg;
                    return FALSE;
                }
                if (!$this->_DB->genQuery('DELETE FROM menu where id = ?', array($n[0]))) {
                    $this->errMsg = $this->_DB->errMsg;
                    return FALSE;
                }
            }
        }

        return TRUE;
    }
}