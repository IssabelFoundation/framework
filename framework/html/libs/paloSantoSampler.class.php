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
  $Id: paloSantoSampler.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

/**
 * Estructura de tablas del muestreo de valores:
 *
 * En la base de datos de muestreo (/var/www/db/sampler.db) existen 4 tablas.
 * La tabla graph define un gráfico, con una PK en graph.id y una etiqueta en
 * graph.name.
 * La tabla line define un conjunto de muestreos que pueden aparecer en uno o
 * más gráficos definidos por la tabla graph. Esta tabla tiene una PK en line.id,
 * un tipo de línea (?) en line.line_type, una etiqueta en line.name y un color
 * en line.color.
 * La tabla graph_vs_line define cuáles líneas aparecen en cuáles gráficos. La
 * asociación se define por graph_vs_line.id_graph FK->graph.id y por
 * graph_vs_line.idline FK->line.id.
 * La tabla samples tiene los valores del muestreo de una línea en función del
 * tiempo. Se tiene PK en samples.id, samples.id_line FK->line.id, un timestamp
 * de UNIX en samples.timestamp y el valor en samples.value.
 *
 * @author alex
 *
 */

class paloSampler {

    var $rutaDB;
    var $errMsg;
    var $_db;

    function paloSampler()
    {
        global $arrConf;
        $this->rutaDB = $arrConf['elastix_dsn']['samples'];
        //instanciar clase paloDB
        $pDB = new paloDB($this->rutaDB);
        if(!empty($pDB->errMsg)) {
            echo "$pDB->errMsg <br>";
        }else{
            $this->_db = $pDB;
        }
    }

    /**
     * Procedimiento que inserta una nueva muestra para la línea indicada.
     *
     * @param   int     $idLine     ID de la línea en la DB
     * @param   int     $timestamp  Timestamp de UNIX para la muestra
     * @param   number  $value      Valor a insertar
     */
    function insertSample($idLine, $timestamp, $value)
    {
        $this->errMsg='';
        $query = 'INSERT INTO samples (id_line, timestamp, value) VALUES (?, ?, ?)';
        $bExito = $this->_db->genQuery($query, array($idLine, $timestamp, $value));
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Procedimiento para leer las muestras para la línea cuyo line.id se indica.
     *
     * @param   int     $idLine     ID de la línea en la DB
     *
     * @return  array   Arreglo vacío en error, o lista de tuplas (timestamp, value)
     */
    function getSamplesByLineId($idLine)
    {
        $this->errMsg='';
        $query = 'SELECT timestamp, value FROM samples WHERE id_line = ?';
        $arrayResult = $this->_db->fetchTable($query, TRUE, array($idLine));
        if (!$arrayResult) {
            $this->errMsg = $this->_db->errMsg;
            return array();
        }
        return $arrayResult;
    }

    /**
     * Procedimiento para cargar la lista de líneas que pertenecen al gráfico
     * cuyo graph.id se indica en el parámetro
     *
     * @param   int $idGraph    ID del gráfico en la DB
     *
     * @return  array   Arreglo vacío en error, o lista de tuplas (id, name, color, line_type)
     */
    function getGraphLinesById($idGraph)
    {
        $this->errMsg='';
        $arrReturn=array();
        $query = <<<SQL_GRAPH_LINES
SELECT l.id as id, l.name as name, l.color as color, l.line_type as line_type
FROM graph_vs_line as gl, line as l
WHERE gl.id_line = l.id AND gl.id_graph = ?
SQL_GRAPH_LINES;
        $arrayResult = $this->_db->fetchTable($query, TRUE, array($idGraph));
        if (!$arrayResult) {
            $this->errMsg = "It was not possible to obtain information about the graph - ".
                $this->_db->errMsg;
            return array();
        }
        return $arrayResult;
    }

    /**
     * Procedimiento para leer el nombre del gráfico, dado el ID.
     *
     * @param   int   $idGraph    ID del gráfico en la DB
     *
     * @return array    Arreglo vacío en error, o elemento "name".
     */
    function getGraphById($idGraph)
    {
        $this->errMsg='';
        $query  = 'SELECT name FROM graph WHERE id = ?';

        $arrayResult = $this->_db->getFirstRowQuery($query, TRUE, array($idGraph));
        if (!$arrayResult) {
            $this->errMsg = "It was not possible to obtain information about the graph - ".
                $this->_db->errMsg;
            return array();
        }
        return $arrayResult;
    }

    /**
     * Procedimiento para eliminar todas las muestras cuyo timestamp sea igual
     * o anterior al timestamp indicado en el parámetro
     *
     * @param   int $timestamp  Timestamp de UNIX para recorte
     *
     * @return  boolean FALSE en error, o TRUE en éxito.
     */
    function deleteDataBeforeThisTimestamp($timestamp)
    {
        $this->errMsg='';
        if (empty($timestamp)) return false;
        $query = 'DELETE FROM samples WHERE timestamp <= ?';
        $bExito = $this->_db->genQuery($query, array($timestamp));
        if (!$bExito) {
            $this->errMsg = $this->_db->errMsg;
            return false;
        }
        return true;
    }
}