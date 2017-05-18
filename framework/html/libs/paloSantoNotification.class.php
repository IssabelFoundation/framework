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
 */

class paloNotification
{
    private $_DB;
    var $errMsg;

    function __construct(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB = $pDB;
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

    function listPublicNotifications($limit = NULL)
    {
        $sql = 'SELECT id, datetime_create, level, id_user, id_resource, content '.
            'FROM acl_notification WHERE id_user IS NULL ORDER BY datetime_create DESC';
        $param = array();
        if (!is_null($limit)) {
            $sql .= ' LIMIT ? OFFSET 0';
            $param[] = $limit;
        }
        $recordset = $this->_DB->fetchTable($sql, TRUE, $param);
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return $recordset;
    }

    function listUserNotifications($id_user, $limit)
    {
        $sql = 'SELECT id, datetime_create, level, id_user, id_resource, content '.
            'FROM acl_notification WHERE id_user = ? ORDER BY datetime_create DESC';
        $param = array($id_user);
        if (!is_null($limit)) {
            $sql .= ' LIMIT ? OFFSET 0';
            $param[] = $limit;
        }
        $recordset = $this->_DB->fetchTable($sql, TRUE, $param);
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return $recordset;
    }

    function getNotification($id_user, $id_notification)
    {
        $sql = 'SELECT id, datetime_create, level, id_user, id_resource, content '.
                'FROM acl_notification WHERE AND id = ?';
        $param = array($id_notification);
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, $param);
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        if (count($tupla) <= 0) {
            $this->errMsg = _tr('Notification not found');
            return FALSE;
        }
        if (!is_null($tupla['id_user']) && $tupla['id_user'] != $id_user) {
            $this->errMsg = _tr('Access denied to private notification');
            return FALSE;
        }
        return $tupla;
    }

    function deleteNotification($id_user, $id_notification)
    {
        if (!$this->getNotification($id_user, $id_notification)) return FALSE;
        $sql = 'DELETE FROM acl_notification WHERE id_user = ? AND id_notification = ?';
        $param = array($id_user, $id_notification);
        if (!$this->_DB->genQuery($sql, $param)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    function insertNotification($level, $content, $id_user = NULL, $id_resource = NULL)
    {
        if (!in_array($level, array('info', 'warning', 'error'))) {
            $this->errMsg = _tr('Invalid notification level');
            return FALSE;
        }

        // Verificar ID de usuario
        if (!is_null($id_user)) {
            $field = (ctype_digit("$id_user")) ? 'id' : 'name';
            $sql = "SELECT id FROM acl_user WHERE {$field} = ?";
            $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, array($id_user));
            if (!is_array($tupla)) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
            if (count($tupla) <= 0) {
                $this->errMsg = _tr('Invalid user');
                return FALSE;
            }
            $id_user = (int)$tupla['id'];
        }

        // Verificar ID de recurso
        if (!is_null($id_resource)) {
            $field = (ctype_digit("$id_resource")) ? 'id' : 'name';
            $sql = "SELECT id FROM acl_resource WHERE {$field} = ?";
            $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, array($id_resource));
            if (!is_array($tupla)) {
                $this->errMsg = $this->_DB->errMsg;
                return FALSE;
            }
            if (count($tupla) <= 0) {
                $this->errMsg = _tr('Invalid resource');
                return FALSE;
            }
            $id_resource = (int)$tupla['id'];
        }

        $sql = 'INSERT INTO acl_notification (datetime_create, level, id_user, id_resource, content) VALUES (?, ?, ?, ?, ?)';
        $param = array(date('Y-m-d H:i:s'), $level, $id_user, $id_resource, $content);
        if (!$this->_DB->genQuery($sql, $param)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }
}
?>