<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
  $Id: paloSantoAjax.class.php, Thu 20 May 2021 08:23:54 AM EDT, nicolas@issabel.com
*/

require_once "libs/xajax/xajax.inc.php";
class paloSantoAjax {

    var $xajax;
    var $functionName;
    var $printJavascript;
    var $smarty;

    function __construct($smarty)
    {
        $this->smarty = $smarty;
    }


    function process($functionName, $arrArgs)
    {
        $this->xajax = new xajax();
        $this->xajax->registerFunction($functionName);
        $this->xajax->processRequests();
        $this->functionName = $functionName;


        $i=0;
        $args = "";
        foreach($arrArgs as $key => $arg){
            if($i==0)
                $args = "$arg";
            else $args .= ", $arg";
            $i++;
        }
        $javascript = $this->xajax->printJavascript("libs/xajax/");
        $div = "<div id='id_".$this->functionName."'></div>
                <script type='text/javascript'>
                    xajax_".$this->functionName."($args);
                </script>";
        $this->printJavascript = $javascript.$div;
        return $javascript.$div;
    }

    function sendResponse($functionName, $content)
    {
        $respuesta = new xajaxResponse();
        $respuesta->addAssign("id_".$functionName,"innerHTML",$content);
        return $respuesta;
    }
}
?>
