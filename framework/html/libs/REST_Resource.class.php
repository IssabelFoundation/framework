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