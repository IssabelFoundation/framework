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
  $Id: paloSantoTree.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class paloTree {

    var $errMsg;
    var $insDB;
    var $nivel;
    var $arrNodosAbiertos;
    var $idNodoActual;

    var $RUTA_IMG;
    var $URLBase;

    var $arrNodos;

    function paloTree($arrNodos)
    {
        $this->errMsg = "";
        $this->nivel = 0;
        $this->arrNodosAbiertos = array();

        /* Para determinar si un elemento es contenedor o no, hay 2 metodos. (1) Ya sea ingresando una bandera
           en la DB para identificar a los contenedores o (2) suponiendo que todo elemento que no contiene
           otros elementos hijos es un elemento mas no un contenedor. El ultimo metodo no es del todo correcto
           pero es util y valido en ciertos enfoques. El modoJerarquia entonces es 1 (uno) para el metodo 1 y
           0 para el metodo 2. */

        $this->URLBase='';
        $this->arrNodos = $arrNodos;
    }

    function setURLBase($url)
    {
        $this->URLBase = $url;

    }

    function setRutaImage($url)
    {
        $this->RUTA_IMG = $url;
    }

    function obtenerNodos()
    {
        $this->errMsg = "";
        return $this->arrNodos;
    }

    function obtenerNodo($id_nodo)
    {
        $this->errMsg = "";
        foreach($this->arrNodos as $k => $v) {
            if($v['id_nodo']==$id_nodo) {
                return $v;
            }
        }
        return false;
    }

    function obtenerNodosAbiertos()
    {
        return $this->arrNodosAbiertos;
    }

    function obtenerNodosRaiz()
    {
        return $this->obtenerContenidoNodo(NULL);
    }

    // Devuelve todos los nodos hijos de un nodo dado
    function obtenerContenidoNodo($id_nodo) 
    {
        $this->errMsg = "";
        foreach($this->arrNodos as $k => $v) {
            if($v['id_parent']==$id_nodo) {
                $arrRes[] = $v;
            }
        }
        return $arrRes;
    }

    function obtenerParent($id_nodo)
    {
        $arrNodo = $this->obtenerNodo($id_nodo);
        return $arrNodo['id_parent'];
    }

    function esNodo($id_elemento)
    {
        // Otro metodo para determinar si es nodo podria ser verificar si no contiene cuentas
        // hijas. Esto haria que el campo tipo_jerarquia no sea necesario y tambien haria posible
        // convertir elementos que no son nodos, en nodos. Simplemente asociandoles una cuenta.
         $this->errMsg = "";
         $arrContenidoNodo = $this->obtenerNodo($id_elemento);

         if(is_array($arrContenidoNodo) and count($arrContenidoNodo)>0) {

            if($arrContenidoNodo["tipo"]=='C')
               return true;
            else
               return FALSE;
         } else {
             return false;
         }

   }

   function dibujaArbol($id_nodo, $nivel=0, $dibujar_arbol_abierto=0)
   {
      // no se si deberia verificar primero si el primer id ingresado es un id de nodo ya
      // que no tiene sentido que alguien pretenda dibujar un arbol a partir de un elemnento
      // en lugar de un nodo
      $nivel++;
      $salida="";
      $arrElementos = $this->obtenerContenidoNodo($id_nodo);

      if($arrElementos===FALSE){
          return FALSE;
      }

      foreach ($arrElementos as $elemento) {
         if($this->esNodo($elemento['id_nodo'])) {
            $salida .= $this->dibujaFolder($elemento["id_nodo"], $nivel);
            // Si el nodo esta abierto, entonces dibujo el subarbol
            if($this->esNodoAbierto($elemento["id_nodo"]) or $dibujar_arbol_abierto) {
               $salida .= $this->dibujaArbol($elemento["id_nodo"], $nivel);
            }
         } else {
            // debido a que es un elemento, lo dibujo
            $salida .= $this->dibujaDoc($elemento["id_nodo"], $nivel);
         }
      }
      return $salida;
    }

    function esNodoAbierto($id_nodo)
    {
      return in_array($id_nodo, $this->arrNodosAbiertos);
    }

    function actualizarNodosAbiertos($id_nodo, $arrNodosAbiertos, $modo=0)
    {
      // Si modo es falso o cero, la funcion se comportara de tal manera que si se especifica
      // un nodo que ya esta abierto lo cerrara (lo eliminara del arreglo). Si modo es verdadero
      // entonces si se especifica un nodo que ya esta abierto, no se hara nada y se lo dejara alli nomas.

      if(!is_array($arrNodosAbiertos)) {
         $arrNodosAbiertos = array();
      }

      $this->arrNodosAbiertos = $arrNodosAbiertos;
      $this->idNodoActual = $id_nodo;
      // Si ya es un nodo abierto, lo remuevo,
      // y si no es un nodo abierto, lo aniado
      if ($this->esNodoAbierto($id_nodo)) {
         if($modo==0) {
            $key = array_search($id_nodo, $this->arrNodosAbiertos);
            unset($this->arrNodosAbiertos[$key]);
         }
      } else {
         $this->arrNodosAbiertos[] = $id_nodo;
      }
      return $this->arrNodosAbiertos;
    }


    // Esta funcion "toca" un nodo. Es decir que si está abierto lo cierra y si está cerrado lo abre.
    // Si modo es 1, entonces siempre me aseguro de que el nodo este en el arreglo
    // TODO: Por ahora no se ha considerado abrir todos los modos padres si es que se abre un nodo hijo.
    function tocarNodo($id_nodo, $modo=0)
    {
        if(!is_array($this->arrNodosAbiertos)) {
            $this->arrNodosAbiertos = array();
        }

        if(in_array($id_nodo, $this->arrNodosAbiertos)) {
            // El nodo ya estaba, lo quito, a menos que modo este en 1
            if($modo!=1) {
                $key=array_search($id_nodo, $this->arrNodosAbiertos);
                unset($this->arrNodosAbiertos[$key]);
            }
        } else {
            // El nodo no estaba, lo pongo
            $this->arrNodosAbiertos[] = $id_nodo;
        }
    }

    function dibujaFolder($id_elemento, $nivel)
    {
      $nivel++;
      $salida="";
      $nodo = $this->obtenerNodo($id_elemento);
      for($i=1; $i<=($nivel-2); $i++) {
         $salida .= "<img src='".$this->RUTA_IMG."/empty.gif'>";
      }

      $url_link=$this->URLBase."&id_nodo=$id_elemento";

      // veo si tengo que dibujar una carpeta abierta o cerrada
      if($this->esNodoAbierto($id_elemento)) {
         $salida .= "<a href='$url_link' class='link_ayuda'><img src='".$this->RUTA_IMG."/minus.gif' border=0></a>";
         $salida .= "<img src='".$this->RUTA_IMG."/folderopen.gif'>";
      } else {
         $salida .= "<a href='$url_link' class='link_ayuda'><img src='".$this->RUTA_IMG."/plus.gif' border=0></a>";
         $salida .= "<img src='".$this->RUTA_IMG."/folder.gif'>";
      }
//      $salida .= "<a href='$url_link' class='link_ayuda'";
      $salida .= "<a href='frameRight.php?id_nodo=$id_elemento' target='contenido' class='link_ayuda'";
    
      // Esta linea la aniadi para pruebas 
      $salida .= " onClick=\"parent.navegacion.location='" . $this->URLBase . "&id_nodo=" . $id_elemento . "'\"";

      if($id_elemento==$this->idNodoActual) {
         $salida .= " style='background: #FDEEB5;'";
      }
      $salida .= "><font class='letra_11'>" . $nodo["nombre"] . "</font></a><br>\n";
      return $salida;
    }

    function dibujaDoc($id_elemento, $nivel)
    {
      $nivel++;
      $salida=$style="";
      $nodo = $this->obtenerNodo($id_elemento);
	  $name_nodo = $nodo['nombre'];
      $link="frameRight.php?id_nodo=$id_elemento&name_nodo=$name_nodo";

      for($i=1; $i<=($nivel-2); $i++) {
         $salida .= "<img src='".$this->RUTA_IMG."/empty.gif'>";
      }
      $salida .= "<img src='".$this->RUTA_IMG."/join.gif'>";
      $salida .= "<img src='".$this->RUTA_IMG."/page.gif'>";

      if($id_elemento==$this->idNodoActual) {
         $style = "style='background: #FDEEB5;'";
      }
      ///Se construye el link
      $salida .= "<a href='$link' $style target='contenido' class='link_ayuda'".
                  "onClick=\"parent.navegacion.location='" . $this->URLBase . "&id_nodo=" . $nodo['id_nodo']."&name_nodo='".$nodo['nombre']."'\">".
                     "<font class='letra_11'>" . $nodo['nombre'] . "</font>".
                  "</a><br>\n";

      return $salida;
    }

    ///Obtiene la ruta del archivo HTML en base a los parents del nodo
   function obtenerRuta($id_nodo){
      $ruta="";
      $this->errMsg = "";
      $sql=$this->QueryBase;
      $sql.=" WHERE id_nodo=$id_nodo";

         if(is_null($id_nodo))
            return "";

      $arrRes = $this->insDB->getFirstRowQuery($sql,true);
         if(is_array($arrRes) && count($arrRes)>0){
            $ruta.=$this->obtenerRuta($arrRes['id_parent']);
            $ruta.="/".$arrRes['url'];
            return $ruta;
         }
         else{
            $this->errMsg = $this->insDB->errMsg;
            return "";
         }
   }

   function obtenerNodoModulo($modulo){
      $sql="SELECT id_nodo,tipo FROM ".$this->arrNombreTabla." WHERE modulo='$modulo'";
      $result=$this->insDB->getFirstRowQuery($sql,true);
         if(is_array($result) && count($result)>0){
            if($result['tipo']=='C'){
               //Se busca el primer nodo hijo, en base al orden y se lo devuelve
               $sQuery="SELECT id_nodo FROM ".$this->arrNombreTabla." WHERE id_parent=".$result["id_nodo"];
               $fila=$this->insDB->getFirstRowQuery($sQuery,true);
                  if(is_array($fila) && count($fila)>0){
                     return $fila['id_nodo'];
                  }
                  else
                     return NULL;
            }
            else
               return $result["id_nodo"];
         }
         else
            return NULL;


   }

   function eliminar_nodos(){
     $db=$this->insDB;
     $sQuery[]="TRUNCATE TABLE help_nodo";
     $sQuery[]="TRUNCATE TABLE help_ocurrencia";
     $sQuery[]="TRUNCATE TABLE help_palabra";
       foreach($sQuery as $sql)
         $db->genQuery($sql);
     
   }
   
   function crearNodos($dir_base,$id_parent=NULL){

      $oDB = $this->insDB;

      $directorio = $dir_base;

      if(!file_exists($directorio)){
         $this->errMsg.="El directorio base no existe<br />";
         return FALSE;
      }

      if(!is_readable($directorio)){
         $this->errMsg.="No tiene permiso para acceder al directorio base<br />";
         $this->errMsg.=$directorio." .<br />\n".$this->getMessage();
         return FALSE;
      }

      $dir = opendir($directorio);
      if($dir===FALSE){
         $this->errMsg.="No se pudo acceder al directorio<br />";
         return FALSE;
      }
      $directorios_sin_permisos=array();

      $bValido = TRUE;
      while($file=readdir($dir)){
         $path=$directorio."/".$file;   
      
         if(is_readable($path) && $file!="." && $file!=".." && !preg_match("/~/",$file) && !preg_match("/^\.*$/",$file) && !preg_match("/CVS/i",$file)){
            if(is_dir($path) )
               $tipo="C";  
            else
               $tipo="A";
               
               
             if(is_null($id_parent)){
               $id_parent="NULL";
             }

          $nombre=$file;   
               
             if(preg_match("/html/i",$file))
                $nombre=basename($file, ".html");
             elseif(preg_match("/htm/i",$file))
                   $nombre=basename($file, ".htm");
                   
                                               
           $sQuery="INSERT INTO help_nodo (tipo,orden,nombre,id_parent,url) ".
           "VALUES ('$tipo',1,'$nombre',$id_parent,'$file')";
           
           
           $bValido=$oDB->genQuery($sQuery);
              if($bValido && $tipo=="C"){
                 $sQuery2="SELECT LAST_INSERT_ID()";
                 $result=$oDB->getFirstRowQuery($sQuery2);
                    if(is_array($result) && count($result)>0){
                         $this->crearNodos($dir_base."/".$file,$result[0]);                
                    }
              }                       
            
         }
         else{ // no es readable
            $directorios_sin_permisos[]=$directorio."/".$file;
         }
      }
      closedir($dir);
      return $directorios_sin_permisos;
   }

   
   
   
   function cargarKeywords(){
      $db=$this->insDB;
      $arr_nodos=$this->obtenerNodos();
      $prefijo="../contenido";
      
        foreach($arr_nodos as $nodo){
           $id_nodo=$nodo['id_nodo'];
           $url=$this->obtenerRuta($id_nodo);
           $url=$prefijo.$url;
          
           
           
             if(file_exists($url) && is_readable($url)){
                $html=file_get_contents($url);
                //<META NAME="keywords" CONTENT="introduccion, test, solo, palosanto, palo, santo, solutions, test">
                $patron="<META[[:space:]]+NAME[[:space:]]*=[[:space:]]*\"keywords\"[[:space:]]+CONTENT[[:space:]]*=[[:space:]]*\"([^\"]*)";
                  if(preg_match("/$patron/i",$html,$regs)){
                        $keywords=$regs[1];
                        $sQuery="UPDATE help_nodo SET keywords=\"$keywords\" WHERE id_nodo=$id_nodo";
                        $bValido=$db->genQuery($sQuery);
            
                  }
                  
                $patron2="<META[[:space:]]+modulo[[:space:]]*=[[:space:]]*\"([^\"]*)";
                   if(preg_match("/$patron2/i",$html,$regs)){
                      $modulo=$regs[1];
                      //print_r($regs);
                      $sQuery="UPDATE help_nodo SET modulo=\"$modulo\" WHERE id_nodo=$id_nodo";
                      $bValido=$db->genQuery($sQuery);
                   }
             }
        
        }
   }
   
}
?>
