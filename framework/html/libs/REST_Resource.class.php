<?php
/*
  vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  | Copyright (c) 1997-2003 Palosanto Solutions S. A.                    |
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
  | Autores: Alberto Santos Flores <asantos@palosanto.com>               |
  +----------------------------------------------------------------------+
  $Id: REST_Resource.class.php,v 1.1 2012/02/07 23:49:36 Alberto Santos Exp $
*/

/**
 * Clase base que representa un recurso direccionable por un URI. El recurso
 * en sí puede o no existir, pero tiene una dirección URL. Si el recurso debe
 * implementar un método HTTP en particular, entonces debe definir un método
 * llamado HTTP_{METODO}, el cual será reportado automáticamente.
 */
class REST_Resource
{
     protected $resourcePath;
	/**
     * Procedimiento que implementa la respuesta al método estándar OPTIONS. Por
     * omisión se asume que se pueden realizar todos los métodos HTTP para los 
     * cuales hay un método definido en el objeto.
     * 
     * @return void
	 */
    function HTTP_OPTIONS()
    {
    	$sAllow = 'Allow: ';
        $listaPermitida = array();
        foreach (get_class_methods($this) as $http_method) {
        	if (substr($http_method, 0, 5) == 'HTTP_')
                $listaPermitida[] = substr($http_method, 5);
        }
        Header($sAllow.implode(', ', $listaPermitida));
    }
}

?>
