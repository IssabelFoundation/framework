<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 0.5                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
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
  $Id: paloSantoConfig.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

if (is_null(LOCK_SH)) define (LOCK_SH, 1);
if (is_null(LOCK_EX)) define (LOCK_EX, 2);
if (is_null(LOCK_UN)) define (LOCK_UN, 3);

/* Clase que encapsula la manipulacion de los archivos de configuracion */

class paloConfig
{
    var $errMsg = "";	// Mensaje explicativo del error ocurrido
    var $directorio  = "";
    var $archivo = "";
    var $separador="=";
    var $separador_regexp="";
    var $usuario_proceso = "asterisk";
    var $usuario_sistema = "root";
    var $grupo_sistema ="root";

    function paloConfig($directorio, $archivo, $separador="", $separador_regexp="", $usuario_proceso=NULL)
    {
        $this->directorio=$directorio;
        $this->archivo=$archivo;
        $this->separador=$separador;
        $this->separador_regexp=$separador_regexp;
            
        if(!is_null($usuario_proceso)) {
            $this->usuario_proceso=$usuario_proceso;
        } else {
            $arr_user=posix_getpwuid(posix_getuid());
            if(is_array($arr_user) && array_key_exists("name",$arr_user)) {
                $this->usuario_proceso=$arr_user['name'];
            }
        }

        //Debo setear el usuario de sistema y el grupo dependiendo del usuario y grupo propietario del archivo
        $ruta_archivo=$directorio."/".$archivo;

        if(file_exists($ruta_archivo)) {
            $arr_usuario=posix_getpwuid(fileowner($ruta_archivo)); 
            if(is_array($arr_usuario)) {
                $this->usuario_sistema=$arr_usuario['name'];
            }
            $arr_grupo=posix_getgrgid(filegroup($ruta_archivo));
            if(is_array($arr_grupo)) {
                $this->grupo_sistema=$arr_grupo['name'];
            }
        }
        /*
        echo "ruta_archivo=".$ruta_archivo."<br>usuario_sistema=".$this->usuario_sistema."<br>grupo_sistema=".
             $this->grupo_sistema."<br>usuario_proceso= ".$this->usuario_proceso."<br>";
        echo "<script>alert('alto =)');</script>";
        */
    }
     
    function setMessage($msg)
    {
        $this->errMsg=$msg;
    }

    function getMessage()
    {
        return $this->errMsg;
    }

	/*
        Procedimiento para leer la configuracion del archivo  almacenarla en un arreglo
	    asociativo que se devuelve como respuesta. El arreglo contiene la siguiente informacion
	    sobre la maquina:
	    La funcion devuelve el arreglo con la informacion requerida, o null en caso de error.
	    En caso de error, se asigna el valor de $this->errMsg para reflejar la causa del error.
	*/

	function leer_configuracion($bComentarios=true)
	{
		$this->errMsg = "";					// Anular cualquier error previo

        // Contenidos de los archivos leidos
        $contenido_archivo = null;

        if($bComentarios==false) {
            $contenido_archivo = $this->privado_leer_claves_archivo($this->directorio."/".$this->archivo, $this->separador_regexp, false);
        } else {
            $contenido_archivo = $this->privado_leer_claves_archivo($this->directorio."/".$this->archivo, $this->separador_regexp);
        }
        
        return $contenido_archivo;
	}

    function escribir_configuracion($arr_reemplazos, $overwrite=FALSE)
	{

		$continuar = true;
		$this->errMsg = "";
		// Construir los usuarios y grupos de los archivos a modificar
		$usuario_grupo = $this->usuario_proceso.".".$this->usuario_proceso;
		$usuario_viejo = $this->usuario_sistema.".".$this->grupo_sistema;

		// Contenidos de los archivos modificados
		$contenido_archivo = null;

		$lista_archivos[$this->archivo] = array("nombre" => $this->directorio."/".$this->archivo,"separador"=>$this->separador,	
                                                "separador_regexp" => $this->separador_regexp,	"contenido" => null);

		$cambio_permisos_directorios = false;
		$cambio_permisos_archivos = false;

		// Se cambia el dueño del directorio temporalmente a nobody.nobody para poder modificarlos
		if ($this->privado_chown($usuario_grupo, $this->directorio)) {
            $cambio_permisos_directorios = true;
		} else {
		    $mensaje = $this->errMsg;

		    // Deshacer los cambios que hayan alcanzado a suceder
		    $this->privado_chown($usuario_viejo, $this->directorio);
		    $this->errMsg = "Al cambiar permisos de directorio ".$this->directorio .": $mensaje";
		    $continuar = false;
		}

		// Se cambia el dueo de los archivos que se van a modificar

		if ($continuar) {
		    $cambio_permisos_archivos = true;
		    foreach ($lista_archivos as $info_archivo) {
			    $cambio_permisos_archivos = ($cambio_permisos_archivos && $this->privado_chown($usuario_grupo, $info_archivo["nombre"]));
            }

		    if (!$cambio_permisos_archivos) {
			    $mensaje = $this->errMsg;
			    foreach ($lista_archivos as $info_archivo) {
                    $this->privado_chown($usuario_viejo, $info_archivo["nombre"]);
                }
			    $this->errMsg = "Al cambiar permiso de archivo: $mensaje";
			    $continuar = false;
		    }
			
			// Se procede a leer el contenido de todos los archivos de configuracin

			if ($continuar) {
			    foreach ($lista_archivos as $clave => $info_archivo) {
			        $continuar = ($continuar && is_array($lista_archivos[$clave]["contenido"] =			      
                                $this->privado_leer_claves_archivo($lista_archivos[$clave]["nombre"],
			                    $lista_archivos[$clave]["separador_regexp"])));
                }

			 	if (!$continuar) {
				    $this->errMsg = "Al leer archivos del sistema: ".$this->errMsg;
				    $continuar = false;
			    }

			}

			// Si fue exitosa la lectura, se reemplazan las claves de los archivos con los valores nuevos

			if ($continuar) {
                if(!$overwrite) {
                    foreach($arr_reemplazos as $clave=>$valor) {
                        $this->privado_set_valor($lista_archivos[$this->archivo]["contenido"],$clave,$valor);
                    }
                } else {
                    // Si esta seteado overwrite=TRUE entonces se escribe el contenido del archivo linea por linea el contenido de $arr_reemplazos
                    $lista_archivos[$this->archivo]["contenido"]=array();
                    $i=0;
                    foreach($arr_reemplazos as $fila) {
                        $lista_archivos[$this->archivo]["contenido"][$i]=$fila;
                        $i++;
                    }
                }
			}

			// Escribir los archivos nuevos en las ubicaciones de los archivos anteriores
			// pero con una extensin .new

			if ($continuar) {                                              
                foreach (array_keys($lista_archivos) as $clave) {
                    if ($continuar) {
                        $exito = $this->privado_escribir_claves_archivo(
                                        $lista_archivos[$clave]["nombre"].".new",
                                        $lista_archivos[$clave]["contenido"],
                                        $lista_archivos[$clave]["separador"]);

                        if (!$exito) {
                            $continuar = false;
                            $this->errMsg = "Al escribir archivo ".
                            $lista_archivos[$clave]["nombre"].".new: ".$this->errMsg;
                        }
                    }
                }
                                
			}

			// Si se pueden escribir todos los archivos, se procede a cambiar de nombres a los archivos
			// originales a extensin .old, y a los archivos nuevos sin extensin .new

			if ($continuar) {
			    foreach (array_keys($lista_archivos) as $clave) {
				    if ($continuar) {
					    $sNuevoNombre = $lista_archivos[$clave]["nombre"].".old";

 			            $exito = rename($lista_archivos[$clave]["nombre"],$sNuevoNombre);
				        if (!$exito) {
						    $continuar = false;
						    $this->errMsg = "No se pudo renombrar archivo ".
						    $lista_archivos[$clave]["nombre"]." a ".
					        $sNuevoNombre;
					    }
				    }
	            }
			}

            if ($continuar) {
			    foreach (array_keys($lista_archivos) as $clave) {
			        if ($continuar) {
					     $exito = rename($lista_archivos[$clave]["nombre"].".new",
					                     $lista_archivos[$clave]["nombre"]);
				        if (!$exito) {
					   	    $continuar = false;
						    $this->errMsg = "No se pudo renombrar archivo ".
						                       $lista_archivos[$clave]["nombre"].".new a ".
						                       $lista_archivos[$clave]["nombre"];
						}
				    }
				}
			}
             
            // Si se pudieron renombrar los archivos, se procede a borrar los respaldos
            if ($continuar) {
                foreach (array_keys($lista_archivos) as $clave) {
                    if ($continuar) {
                        $exito = unlink($lista_archivos[$clave]["nombre"].'.old');
                        if (!$exito) {
                            $continuar = false;
                            $this->errMsg = "No se puede borrar archivo de respaldo ".
                                $lista_archivos[$clave]["nombre"].'.old';
                        }
                    }
                }
            }

			// Si se alcanz a cambiar los permisos de los directorios, se deshace el cambio

            if ($cambio_permisos_archivos) {
			    foreach ($lista_archivos as $info_archivo) $this->privado_chown($usuario_viejo, $info_archivo["nombre"]);
			}

			// Si se alcanzo a cambiar los permisos de los directorios, se deshace el cambio

			if ($cambio_permisos_directorios) {
			    // Restaurar los permisos de los directorios a su valor original
				$this->privado_chown($usuario_viejo, $this->directorio);
				$cambio_permisos_directorios = false;
			}

	    } else {
            $this->errMsg = "Al adquirir candado de escritura sobre configuracion de red: No se puede crear archivo de candado.";
        }

		return $continuar;
    }

	/*	
        Funcion que encapsula la llamada a chown en un procedimiento de PHP,
		devuelve VERDADERO si el comando se ejecuta correctamente, FALSO si no.
	 */

    private function privado_chown($usuario, $ruta)
    {   
        $usergroup = explode('.', $usuario);
        if (!chown($ruta, $usergroup[0])) {
            $this->errMsg = 'Failed to chown';
            return FALSE;
        }
        if (count($usergroup) > 1 && !chgrp($ruta, $usergroup[1])) {
            $this->errMsg = 'Failed to chgrp';
            return FALSE;
        }
        return TRUE;
    }

	/*	Funcion que extrae claves de un archivo que contiene claves de la forma CLAVE=VALOR,
		y devuelve las claves indicadas en el arreglo indicado como parametro. Si la linea
		leida inicia con numeral, o no se ajusta a CLAVE=VALOR, entonces se asigna al elemento
		del arreglo la cadena sin parsear. De otro modo, se asigna al element del arreglo, una
		tupla cuyo primer elemento es la CLAVE y el segundo es el VALOR.
		Se devuelve un arreglo con el siguiente contenido del archivo en caso de exito
			array(
				array("clave1", "valor1"),
				array("clave2", "valor2"),
				"#un comentario",
				"texto arbitrario",
				...
				array("clave3", "valor3")
				)
		Se devuelve null en caso de fracaso, y se asigna un valor al texto $this->errMsg.
	*/

	private function privado_leer_claves_archivo($ruta, $separador = "[[:blank:]]*=[[:blank:]]*", $bComentarios=true)
	{
        $bEsClave = false;
	    $lista_claves = null;
	    $archivo = @fopen($ruta, "r");
	    if ($archivo) {
		    // Cargar todo el archivo en memoria
		    $lista_claves = array();
		    while (!feof($archivo)) {
			    $linea_leida = fgets($archivo, 8192);
			    if ($linea_leida) {
				    $linea_leida = chop($linea_leida);
				    // Si la linea leida del archivo coincide con la expresion regular, se asigna
				    // el arreglo de las expresiones encontradas.
					if (preg_match("/^([[:alnum:]._]+)($separador)(.*)$/", $linea_leida, $tupla)) {
					    $linea_leida = array();
					    $linea_leida["clave"] = $tupla[1];
					    $linea_leida["valor"] = $tupla[3];
                        $linea_leida["separador"]=$tupla[2];
                        $linea_leida["comentario"]="";
                        $bEsClave = true;
                    // Comienza con $ o @
				    } elseif(preg_match("/^([\$|\@].*)($separador)([^;]+)(;.*)$/",$linea_leida,$tupla)) {
                        $linea_leida = array();
                        $linea_leida["clave"] = rtrim($tupla[1]);
                        // sacamos comillas simples del freepbx.conf o issabelpbx.conf donde entradas comienzan con $
                        if(substr($tupla[3],0,1)=="'" && substr($tupla[3],-1)=="'") { $tupla[3]=substr($tupla[3],1,-1); }
                        $linea_leida["valor"] = $tupla[3];
                        $linea_leida["separador"]=$tupla[2];
                        $linea_leida["comentario"]=$tupla[4]; 
                        $bEsClave = true;
                    } elseif(preg_match("/^([[:alnum:]._\-\@]+)($separador)(.*)$/",$linea_leida,$tupla)) {
                        $linea_leida= array();
                        $linea_leida["clave"] = $tupla[1];
                        $linea_leida["valor"] = $tupla[3];
                        $linea_leida["separador"]=$tupla[2];
                        $linea_leida["comentario"]="";
                        $bEsClave = true;
                    } else {
                        $bEsClave = false;
                    }

                    if($bComentarios==false) {
                        if($bEsClave==true) {
        				    $lista_claves[$linea_leida["clave"]] = $linea_leida;
                        }
                    } else {
        				    $lista_claves[] = $linea_leida;
                    }
			    }
		    }
		    fclose($archivo);

	    } else {
            $this->errMsg = "No se puede abrir el archivo '$ruta' para lectura.";
        }
	    return $lista_claves;
    }
    
	/*	
        Funcion que escribe el contenido de la lista de claves devuelta por la funcion
		privado_leer_claves_archivo(),
	*/

	private function privado_escribir_claves_archivo($ruta, $lista_claves, $separador = "=")
	{	
	    $exito = false;
	    $archivo = fopen($ruta, "w");
	    if ($archivo) {
		    // Para cada linea, verificar si es arreglo o cadena
		    foreach ($lista_claves as $linea) {
			    if (is_array($linea)) {
                    $linea_archivo=$linea["clave"].$linea["separador"].$linea["valor"].$linea["comentario"]."\n";
				    fputs($archivo, $linea_archivo);
                } else {
                    fputs($archivo, $linea."\n");
                }
		    }

		    fclose($archivo);
		    $exito = true;
	    } else {
            $this->errMsg = "No se puede abrir el archivo '$ruta' para escritura.";
        }
	    return $exito;
    }

	private function privado_indice_clave(&$lista, $clave, $saltar = 0)
	{
	    $posicion = null;
	    $i = 0;

            if(!is_array($lista)) { return 0; }

	    foreach ($lista as $indice => $contenido) {
		    if (is_array($contenido)) {
                if(isset($contenido['clave'])){
                    $nombre_clave=$contenido['clave'];
                    if(trim($nombre_clave)==trim($clave)) {
                        $posicion = $indice;
                    }
                }
		    }
	    }
	    return $posicion;
    }

    function privado_get_valor($lista, $clave, $saltar = 0)
    {

	    $posicion = $this->privado_indice_clave($lista, $clave, $saltar);
	    if (!is_null($posicion)) {
		    return $lista[$posicion]["valor"];
        } else {
            return null;
        }
    }

	private function privado_set_valor(&$lista, $clave, $valor, $saltar = 0)
	{
	    $posicion = $this->privado_indice_clave($lista, $clave, $saltar);
		if (is_null($posicion)) {
			$tupla["clave"] = $clave;
			$tupla["valor"] = $valor;
            $tupla["separador"]=$this->separador; //en caso que no exista en el archivo de configuracion se creara al final
            //Si la clave tiene signo $ o @ se asume que es un script de perl y se pone ; al final
            if(preg_match("/[\$@]/",$tupla["clave"])) {
                $tupla["comentario"]=";";
            } else {
                $tupla["comentario"]="";
            }
                              
		    $lista[] = $tupla;
	    } else {
            $lista[$posicion]["valor"] = $valor;
        }
    }
}
?>
