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
  $Id: paloSantoValidar.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */
global $arrLang;
define("PALOVALIDAR_MSG_ERROR_1",_tr("Empty field"));
define("PALOVALIDAR_MSG_ERROR_2",_tr("Bad Format"));
define("PALOVALIDAR_MSG_ERROR_3",_tr("No option was selected"));
define("PALOVALIDAR_MSG_ERROR_4",_tr("Octets out of range"));
define("PALOVALIDAR_MSG_ERROR_5",_tr("Undefined validation type"));

class PaloValidar 
{

    var $arrErrores;
    var $hacerTrim;

	function PaloValidar()
	{
        $this->hacerTrim=1;
        $this->clear();
    } 

    function clear()
    {
        $this->arrErrores = '';
    }

    function validar($nombre_variable, $variable, $tipo_validacion, $parametro_extra='')
    {
        $return = false;
        if($this->hacerTrim and !is_array($variable)) $variable=trim($variable);
        switch($tipo_validacion) {
            case "name":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    $return = true;
                }
                break;
            case "text": 
            case "":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    $return = true;
                }
                break;
            case "ereg":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else if(empty($parametro_extra)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/$parametro_extra/", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }
                }
                break;
            case "filename":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/^[-_\.[:alnum:]]+$/i", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }                    
                }
                break;
            case "file":
                // "file" difiere de "filename" en que filename verifica solo si es un nombre de archivo
                // valido, mientras que file verifica que se el archivo subido con un control tipo file
                // haya sido transferido correctamente al servidor.
                if($variable['error']!=0) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                    }
                } else {
                    $return = true;
                }                    
                break;
            case "domain":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/^(([[:alnum:]-]+)\.)+([[:alnum:]])+$/i", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }                    
                }
                break;
            case "filepath":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/^(\/*[-_\.[:alnum:]]+)+$/i", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }
                }
                break;
            case "ip":
                if($this->estaVacio($variable)) {
                    $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                } else {
                    if(!preg_match("/^([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})$/",
                              $variable, $arrReg)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        if(($arrReg[1]<=255) and ($arrReg[1]>0) and ($arrReg[2]<=255) and ($arrReg[2]>=0) and
                           ($arrReg[3]<=255) and ($arrReg[3]>=0) and ($arrReg[4]<=255) and ($arrReg[4]>=0)) {
                            $return = true;
                        } else {
                            if($nombre_variable!="just_test") {
                                $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_4;
                            }
                        }
                    }
                }
                break;
            case "mask":
                if($this->estaVacio($variable)) {
                    $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                } else {
                    if(!preg_match("/^([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})$/",
                              $variable, $arrReg)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        if(($arrReg[1]<=255) and ($arrReg[1]>0) and ($arrReg[2]<=255) and ($arrReg[2]>=0) and
                           ($arrReg[3]<=255) and ($arrReg[3]>=0) and ($arrReg[4]<=255) and ($arrReg[4]>=0)) {
                            $return = true;   
                        } else {
                            if($nombre_variable!="just_test") {
                                $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_4;
                            }
                        }
                    }
                }
                break;
            case "ip/mask":
                if($this->estaVacio($variable)) {
                    $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                } else {
                    if(!preg_match("/^([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\.([[:digit:]]{1,3})\/([[:digit:]]{1,2})$/", $variable, $arrReg)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        if(($arrReg[1]<=255) and ($arrReg[1]>0) and ($arrReg[2]<=255) and ($arrReg[2]>=0) and
                            ($arrReg[3]<=255) and ($arrReg[3]>=0) and ($arrReg[4]<=255) and ($arrReg[4]>=0) and 
                            ($arrReg[5]>=0) and ($arrReg[5]<=32)) {
                            $return = true;
                        } else {
                            if($nombre_variable!="just_test") {
                                $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_4;
                            }
                        }
                    }
                }
                break;
            case "numeric":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/^[[:digit:]]+$/i", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }                    
                }
                break;
            case "numeric_range":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test")
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                } else if(empty($parametro_extra)) {
                    if($nombre_variable!="just_test")
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                } else if(!preg_match("/^[[:digit:]]+$/", $variable)) {
                    if($nombre_variable!="just_test")
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                } else if(!preg_match("/^[[:digit:]]+\-[[:digit:]]+$/", $parametro_extra)) {
                    if($nombre_variable!="just_test")
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                } else {
                    $arrRang = explode("-",$parametro_extra);
                    if($variable <= $arrRang[1] && $variable >= $arrRang[0])
                        $return = true;
                    else {
                        if($nombre_variable!="just_test")
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                    }
                }                
                break;
            case "float":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/^[[:digit:]]+(\.[[:digit:]]+)?$/i", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }                    
                }
                break;
            case "numeric_array":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(is_array($variable)) {
                        foreach($variable as $v) {
                            if(!preg_match("/^[[:digit:]]+$/i", $v)) {
                                if($nombre_variable!="just_test") {
                                    $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                                }
                                break;
                            } else {
                                $return = true;
                            }                    
                        }
                    } else {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_3;
                        }
                    }
                }
                break;
            case "ereg_array":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(is_array($variable)) {
                        foreach($variable as $v) {
                            if(!preg_match("/$parametro_extra/i", $v)) {
                                if($nombre_variable!="just_test") {
                                    $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                                }
                                break;
                            } else {
                                $return = true;
                            }                    
                        }
                    } else {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_3;
                        }
                    }
                }
                break;
            case "email":
                if($this->estaVacio($variable)) {
                    if($nombre_variable!="just_test") {
                        $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_1;
                    }
                } else {
                    if(!preg_match("/^[a-z0-9]+([\._\-]?[a-z0-9]+[_\-]?)*@[a-z0-9]+([\._\-]?[a-z0-9]+)*(\.[a-z0-9]{2,4})+$/", $variable)) {
                        if($nombre_variable!="just_test") {
                            $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_2;
                        }
                    } else {
                        $return = true;
                    }                    
                }
                break;
            default:
                $this->arrErrores[$nombre_variable]['mensaje'] = PALOVALIDAR_MSG_ERROR_5;
        }
        return $return;
    }

    function existenErroresPrevios()
    {
        if(empty($this->arrErrores)) return false;
        return true;
    }

    function obtenerArregloErrores()
    {
        return $this->arrErrores;
    }

    function estaVacio($variable)
    {
        return (trim("$variable") == '');
    }
}
?>
