<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2003 Palosanto Solutions S. A.                    |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  +----------------------------------------------------------------------+
  | Este archivo fuente está sujeto a las políticas de licenciamiento    |
  | de Palosanto Solutions S. A. y no está disponible públicamente.      |
  | El acceso a este documento está restringido según lo estipulado      |
  | en los acuerdos de confidencialidad los cuales son parte de las      |
  | políticas internas de Palosanto Solutions S. A.                      |
  | Si Ud. está viendo este archivo y no tiene autorización explícita    |
  | de hacerlo, comuníquese con nosotros, podría estar infringiendo      |
  | la ley sin saberlo.                                                  |
  +----------------------------------------------------------------------+
  | Autores: Gladys Carrillo B.   <gcarrillo@palosanto.com>              |
  +----------------------------------------------------------------------+
  $Id: paloSantoInstaller.class.php,v 1.1 2007/09/05 00:25:25 gcarrillo Exp $
*/

require_once "paloSantoDB.class.php";
require_once "misc.lib.php";

// La presencia de MYSQL_ROOT_PASSWORD es parte del API global.
define('MYSQL_ROOT_PASSWORD', obtenerClaveConocidaMySQL('root', '/var/www/html/'));

class Installer
{

    var $_errMsg;

    function Installer()
    {

    }

    private function _normalizeMenuAttributes($a)
    {
        foreach (array('parent', 'link', 'tag', 'menuid') as $k)
            if (!isset($a[$k])) $a[$k] = '';
        if (!isset($a['order'])) $a['order'] = '-1';
        $a['type'] = ($a['parent'] != '')
            ? (($a['link'] != '') ? 'framed' : 'module')
            : '';
        return $a;
    }

    function addMenu($oMenu, $arrTmp)
    {
        $arrTmp = $this->_normalizeMenuAttributes($arrTmp);
        $bExito = $oMenu->createMenu($arrTmp['menuid'], $arrTmp['tag'],
            $arrTmp['parent'], $arrTmp['type'], $arrTmp['link'],
            $arrTmp['order']);
        if (!$bExito) {
            $this->_errMsg = $oMenu->errMsg;
            return FALSE;
        }
        return TRUE;
    }

 /*****************************************************************************************************/
// funcion para actualizar un item de menu
    function UpdateMenu($oMenu,$arrTmp)
    {
        $arrTmp = $this->_normalizeMenuAttributes($arrTmp);
        $bExito = $oMenu->updateItemMenu($arrTmp['menuid'], $arrTmp['tag'],
            $arrTmp['parent'], $arrTmp['type'], $arrTmp['link'],
            $arrTmp['order']);
        if (!$bExito) {
            $this->_errMsg = $oMenu->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    function updateResourceMembership($oACL, $arrTmp, $arrGroup=array())
    {
        $oACL->_DB->beginTransaction();
        $bExito = $oACL->createResource($arrTmp['menuid'], $arrTmp['tag']);
        if ($bExito){
            $oACL->_DB->commit();
        }else
            $oACL->_DB->rollBack();
        $this->_errMsg = $oACL->errMsg;
        return $bExito;
    }

/*****************************************************************************************************/

    function addResourceMembership($oACL, $arrTmp, $arrGroup=array())
    {
        $bExito = TRUE;
        $grouplist = array();

        $oACL->_DB->beginTransaction();

        if ($bExito)
            $bExito = $oACL->createResource($arrTmp['menuid'], $arrTmp['tag']);
        if ($bExito) {
            $resource_id = $oACL->getResourceId($arrTmp['menuid']);
            $bExito = !is_null($resource_id);
        }
        if ($bExito) {
            if (!(is_array($arrGroup) && count($arrGroup) > 0)) {
                $grouplist[] = 1;   // Esto asume que el grupo 1 es "admin"
            } else {
                foreach ($arrGroup as $g) {
                    $id_group = $oACL->getIdGroup($g['name']);
                    if (!$id_group) {
                        if (!is_null($oACL->getGroupNameByid($g['id']))) {
                            // TODO: verificar que el nombre del grupo es igual
                            $id_group = $g['id'];
                        } else {
                            $bExito = $oACL->createGroup($g['name'], $g['desc']);
                            if (!$bExito) break;
                            $id_group = $oACL->_DB->getLastInsertId();
                        }
                    }
                    $grouplist[] = $id_group;
                }
            }
        }
        if ($bExito) foreach ($grouplist as $id_group) {
            $bExito = $oACL->saveGroupPermission($id_group, array($resource_id));
            if (!$bExito) break;
        }

        $this->_errMsg = $oACL->errMsg;
        if ($bExito)
            $oACL->_DB->commit();
        else
            $oACL->_DB->rollBack();
        return $bExito;
    }

    function createNewDatabase($path_script_db,$sqlite_db_path,$db_name)
    {
        $comando="cat $path_script_db | sqlite3 $sqlite_db_path/$db_name.db";
        exec($comando,$output,$retval);
        return $retval;
    }
    function createNewDatabaseMySQL($path_script_db, $db_name, $datos_conexion)
    {
        $root_password = MYSQL_ROOT_PASSWORD;

        $db = 'mysql://root:'.$root_password.'@localhost/';
        $pDB = new paloDB ($db);
        $sPeticionSQL = "CREATE DATABASE $db_name";
        $result = $pDB->genExec($sPeticionSQL);
        if($datos_conexion['locate'] == "")
            $datos_conexion['locate'] = "localhost";
        $GrantSQL = "GRANT SELECT, INSERT, UPDATE, DELETE ON $db_name.* TO ";
        $GrantSQL .= $datos_conexion['user']."@".$datos_conexion['locate']." IDENTIFIED BY '".$datos_conexion['password']."'";
        $result = $pDB->genExec($GrantSQL);
        $comando="mysql --password=".escapeshellcmd($root_password)." --user=root $db_name < $path_script_db";
        exec($comando,$output,$retval);
        return $retval;
    }

    function refresh($documentRoot='')
    {
        if($documentRoot == ''){
            global $arrConf;
            $documentRoot = $arrConf['basePath'];
        }

        //STEP 1: Delete tmp templates of smarty.
        exec("rm -rf $documentRoot/var/templates_c/*",$arrConsole,$flagStatus);

        //STEP 2: Update menus elastix permission.
        if(isset($_SESSION['elastix_user_permission']))
          unset($_SESSION['elastix_user_permission']);

        return $flagStatus;
    }

    function addResourcePrivileges($oACL, $name, $privileges)
    {
        $bExito = TRUE;

        $oACL->_DB->beginTransaction();

        $resource_id = $oACL->getResourceId($name);
        $bExito = !is_null($resource_id);

        if ($bExito) foreach ($privileges as $privilege) {
            $bExito = $oACL->createModulePrivilege($resource_id, $privilege['name'], $privilege['desc']);
            if (!$bExito) break;
            $id_privilege = $oACL->getIdModulePrivilege($resource_id, $privilege['name']);
            foreach ($privilege['grant2group'] as $gname) {
                $id_group = $oACL->getIdGroup($gname);
                $bExito = $oACL->grantModulePrivilege2Group($id_privilege, $id_group);
                if (!$bExito) break 2;
            }
        }

        $this->_errMsg = $oACL->errMsg;
        if ($bExito)
            $oACL->_DB->commit();
        else
            $oACL->_DB->rollBack();
        return $bExito;
    }
}
