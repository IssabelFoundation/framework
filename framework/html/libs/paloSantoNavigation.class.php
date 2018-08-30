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
  $Id: paloSantoNavigation.class.php,v 1.2 2007/09/07 00:20:03 gcarrillo Exp $ */

define('MENUTAG', 'Name');
define('MAX_THEME_LEVEL', 3);

class paloSantoNavigationBase
{
    protected $_menubase;     // Lista de todos los items de primer nivel
    protected $_menunodes;    // Todos los items de menú, indexados por id de menu
    protected $_selection;    // Arreglo de IDs de menú desde primer nivel hasta selección

    /**
     * Constructor de objeto de manejo del menú. El parámetro arrMenu es un
     * arreglo de tuplas. La clave de cada tupla es el valor del item del menú.
     * Cada tupla contiene los siguientes elementos:
     *  id          Valor de item del menú, debe ser idéntico a la clave de tupla
     *  IdParent    Valor de item del menú que contiene a este menú, o es empty()
     *              si es un item de primer nivel de menú.
     *  Name        Etiqueta a mostrar en el menú para este item
     *  Type        module|framed|empty()
     *  Link        Si no es empty(), plantilla de enlace para Type=framed
     *  order_no    (no se usa)
     *  HasChild    (se sobreescribe según presencia o ausencia de hijos)
     *
     * @param   array   $arrMenu    Lista de menús
     * @param   string  $idMenuSelected Si != NULL, elemento inicial a seleccionar
     * @param   object  $smarty     Referencia a objeto Smarty para plantillas
     */
    function __construct($arrMenu, $idMenuSelected = NULL)
    {
        // Construcción del árbol de menú
        $this->_menubase = array();
        $this->_menunodes = array();
        foreach ($arrMenu as $menuitem) {
            if (empty($menuitem['IdParent'])) $menuitem['IdParent'] = NULL;
            $menuitem['children'] = array();
            $menuitem['parent'] = NULL;
            $menuitem['HasChild'] = FALSE;
            $this->_menunodes[$menuitem['id']] = $menuitem;
        }
        foreach (array_keys($this->_menunodes) as $id) {
            $id_parent = $this->_menunodes[$id]['IdParent'];
            if (!is_null($id_parent) && !isset($this->_menunodes[$id_parent]))
                $this->_menunodes[$id]['IdParent'] = $id_parent = NULL;
            if (is_null($id_parent)) {
                $this->_menubase[$id] = &$this->_menunodes[$id];
            } else {
                $this->_menunodes[$id_parent]['HasChild'] = TRUE;
                $this->_menunodes[$id_parent]['children'][$id] = &$this->_menunodes[$id];
                $this->_menunodes[$id]['parent'] = &$this->_menunodes[$id_parent];
            }
        }
        $this->setSelectedModule($idMenuSelected);
    }

    /**
     * Procedimiento para obtener un arreglo que representa la ruta a través del
     * menú desde el primer nivel hasta el módulo seleccionado. Si el item es
     * un item con hijos, se completa con el primer hijo en cada nivel hasta
     * el último nivel.
     *
     * @param   $idMenuSelected string  Item de menú que se ha seleccionado.
     *
     * @return  mixed   NULL si el árbol está vacío, o la ruta como arreglo
     */
    private function _getMenuSelectionPath($idMenuSelected)
    {
        if (empty($idMenuSelected)) $idMenuSelected = NULL;
        if (!is_null($idMenuSelected) && !isset($this->_menunodes[$idMenuSelected])) {
            $idMenuSelected = NULL;
        }
        if (is_null($idMenuSelected) && count($this->_menubase) > 0) {
            $ak = array_keys($this->_menubase);
            $idMenuSelected = array_shift($ak);
        }
        if (is_null($idMenuSelected)) { return NULL; }

        /* En este punto se $m es el nodo seleccionado, el cual puede estar en
         * cualquier parte del árbol. Primero se navegará por este nodo y los
         * hijos hasta llegar al último nivel. Luego, del mismo nodo, se
         * obtendrán los padres hasta llegar al primer nivel */
        $path = array();
        $m = &$this->_menunodes[$idMenuSelected];
        array_push($path, $m['id']);
        while (count($m['children']) > 0) {
            $a1 = array_keys($m['children']);
            $a2 = array_shift($a1);
            $m = &$m['children'][$a2];
            array_push($path, $m['id']);
        }
        $m = &$this->_menunodes[$idMenuSelected];
        while (!is_null($m['parent'])) {
            $m = &$m['parent'];
            array_unshift($path, $m['id']);
        }
        return $path;
    }

    /**
     * Asignar como item actual el módulo seleccionado según el item indicado
     *
     * @param   $idMenuSelected string  Item de menú que se ha seleccionado.
     *
     * @return  void
     */
    function setSelectedModule($idMenuSelected)
    {
        $this->_selection = $this->_getMenuSelectionPath($idMenuSelected);
    }

    /**
     * Obtener el item actualmente seleccionado
     *
     * @return  mixed   NULL si el elemento es inválido, o el módulo seleccionado
     */
    function getSelectedModule()
    {
        return is_null($this->_selection) ? NULL : $this->_selection[count($this->_selection) - 1];
    }

    /**
     * Obtener la ruta a través del menú hasta el item de módulo seleccionado
     *
     * @return  mixed   NULL si el elemento es inválido, o el módulo seleccionado
     */
    function getSelectedModulePath()
    {
    	return $this->_selection;
    }
}

class paloSantoNavigation extends paloSantoNavigationBase
{
    private $_smarty;       // Objeto Smarty para las plantillas

    function __construct($arrMenu, &$smarty, $idMenuSelected = NULL)
    {
        parent::__construct($arrMenu, $idMenuSelected);
        $this->_smarty = &$smarty;
    }

    // TODO: esta función es usada por extras/developer así que por ahora es pública
    function getArrSubMenu($idParent)
    {
        if (!empty($idParent)) {
            if (!isset($this->_menunodes[$idParent])) return FALSE;
            $children = &$this->_menunodes[$idParent]['children'];
        } else {
            $children = &$this->_menubase;
        }
        $arrSubMenu = array();
        foreach ($children as $element) {
            unset($element['parent']);
            unset($element['children']);
            $arrSubMenu[$element['id']] = $element;
        }
        if (count($arrSubMenu) <= 0) return FALSE;
        return $arrSubMenu;
    }

    function renderMenuTemplates()
    {
    	if (is_null($this->_selection)) die('FATAL: Unable to render with empty menu!');

        // Generar las listas de items de menú en formato compatible con temas
        $menuItemsForThemes = array();
        $nodeListRef = &$this->_menubase;
        $i = 0;
        foreach ($this->_selection as $menuItem) {
        	if ($i >= MAX_THEME_LEVEL) break;

            $menuItemsForThemes[$i] = &$nodeListRef;
            $nodeListRef = &$this->_menunodes[$menuItem]['children'];
            $i++;
        }

        // Asignar las listas genéricas
        $smartyVars = array(
            array('arrMainMenu', 'idMainMenuSelected', 'nameMainMenuSelected'),
            array('arrSubMenu',  'idSubMenuSelected',  'nameSubMenuSelected'),
            array('arrSubMenu2', 'idSubMenu2Selected', 'nameSubMenu2Selected'),
        );
        for ($i = 0; $i < count($menuItemsForThemes); $i++) {
        	$this->_smarty->assign($smartyVars[$i][0], $menuItemsForThemes[$i]);
            $this->_smarty->assign($smartyVars[$i][1], $this->_selection[$i]);
            $this->_smarty->assign($smartyVars[$i][2], $this->_menunodes[$this->_selection[$i]][MENUTAG]);
        }
        $this->_smarty->assign('isThirdLevel', ((count($this->_selection) > 2) ? 'on' : 'off'));

        // Escribir el log de navegación para cada página visitada sin acción alguna
        if (isset($_GET) && count($_GET) == 1 && isset($_GET['menu'])) {
            $tagstack = array();
            foreach ($this->_selection as $key) $tagstack[] = $this->_menunodes[$key][MENUTAG];
            $user = isset($_SESSION['issabel_user']) ? $_SESSION['issabel_user'] : 'unknown';
            writeLOG('audit.log', sprintf('NAVIGATION %s: User %s visited "%s" from %s.',
                $user, $user, implode(' >> ', $tagstack), $_SERVER['REMOTE_ADDR']));
        }
    }

    function showContent()
    {
        $selectedModule = $this->getSelectedModule();
        $this->putHEAD_JQUERY_HTML();

        // Módulo seleccionado es un verdadero módulo con código
        if ($this->_menunodes[$selectedModule]['Type'] == 'module')
            return $this->includeModule($selectedModule);

        // TODO: mover iframe a plantilla
        // Módulo seleccionado es un iframe con un enlace dentro
        $this->_smarty->assign('title', $this->_menunodes[$selectedModule][MENUTAG]);
        $link = $this->_menunodes[$selectedModule]['Link'];
        $link = str_replace('{NAME_SERVER}', $_SERVER['SERVER_NAME'], $link);
        $link = str_replace('{IP_SERVER}', $_SERVER['SERVER_ADDR'], $link);
        return  "<iframe marginwidth=\"0\" marginheight=\"0\" class=\"frameModule\"".
                "\" src=\"$link\" name=\"myframe\" id=\"myframe\" frameborder=\"0\"".
                " width=\"100%\" onLoad=\"calcHeight();\"></iframe>";
    }

    private function includeModule($module)
    {
        if (!file_exists("modules/$module/index.php"))
            return "Error: The module <b>modules/$module/index.php</b> could not be found!<br/>";
        /*
        // Cargar las configuraciones para el módulo elegido
        if (file_exists("modules/$module/configs/default.conf.php")) {
            require_once "modules/$module/configs/default.conf.php";

            global $arrConf;
            global $arrConfModule;
            $arrConf = array_merge($arrConf, $arrConfModule);
        }

        // Cargar las traducciones para el módulo elegido
        load_language_module($module);
        */
        ini_set('include_path', dirname($_SERVER['SCRIPT_FILENAME'])."/modules/$module/libs:".ini_get('include_path'));

        require_once "modules/$module/index.php";
        if (!function_exists("_moduleContent"))
            return "Wrong module: modules/$module/index.php";
        $this->putHEAD_MODULE_HTML($module);
        return _moduleContent($this->_smarty, $module);
    }

    /**
    *
    * Description:
    *   This function put the tags css and js per each module and the libs of the framework
    *
    * Example:
    *   $array = putHEAD_MODULE_HTML('calendar');
    *
    * Developer:
    *   Eduardo Cueva
    *
    * e-mail:
    *   ecueva@palosanto.com
    */
    private function putHEAD_MODULE_HTML($menuLibs)  // add by eduardo
    {
        global $arrConf;

        // FIXME: The theme default shouldn't be static.
        $localtheme = 'default';

        //$HEADER_MODULES
        $this->_smarty->assign("HEADER_MODULES", implode("\n", array_merge(
            $this->_buildScriptTags($arrConf['basePath'], "modules/$menuLibs/themes/$localtheme/js"),
            $this->_buildCSSTags($arrConf['basePath'], "modules/$menuLibs/themes/$localtheme/css")
        )));
    }

    function putHEAD_JQUERY_HTML()
    {
        global $arrConf;

        // TODO: allow custom theme to define a jQueryUI theme
        $jquery_ui_theme = 'ui-lightness';
        switch ($arrConf['mainTheme']) {
            case 'tenant':      $jquery_ui_theme = 'smoothness'; break;
            case 'blackmin':    $jquery_ui_theme = 'smoothness'; break;
            case 'giox':
            case 'elastixblue':
            case 'elastixneo':  $jquery_ui_theme = 'redmond'; break;

            case 'elastixwine':
            case 'default':     $jquery_ui_theme = 'blitzer'; break;
            case 'slashdot':    $jquery_ui_theme = 'start'; break;
        }

        $HEADER_LIBS_JQUERY = $this->_buildScriptTags($arrConf['basePath'], "libs/js/jquery");
        foreach (array('libs/js/jquery/widgetcss', 'libs/js/jquery/css/'.$jquery_ui_theme) as $csspath) {
            $HEADER_LIBS_JQUERY = array_merge($HEADER_LIBS_JQUERY, $this->_buildCSSTags($arrConf['basePath'], $csspath));
        }

        // Se buscan font-icons para referenciar
        $HEADER_LIBS_JQUERY = array_merge($HEADER_LIBS_JQUERY, $this->_buildFontTags($arrConf['basePath'], 'libs/font-icons'));

        $this->_smarty->assign("HEADER_LIBS_JQUERY", implode("\n", $HEADER_LIBS_JQUERY));
    }

    private function _buildScriptTags($documentRoot, $url)
    {
        $tags = array();
        $dirpath = "$documentRoot/$url";
        if (is_dir($dirpath)) {
            foreach ($this->obtainFiles($dirpath, "js") as $file) {
                $tags[] = "<script type='text/javascript' src='{$url}/{$file}'></script>";
            }
        }
        return $tags;
    }

    private function _buildCSSTags($documentRoot, $url)
    {
        $tags = array();
        $dirpath = "$documentRoot/$url";
        if (is_dir($dirpath)) {
            foreach ($this->obtainFiles($dirpath, "css") as $file) {
                $tags[] = "<link rel='stylesheet' href='{$url}/{$file}' />";
            }
        }
        return $tags;
    }

    private function _buildFontTags($documentRoot, $url)
    {
        $tags = array();
        $dirpath = "$documentRoot/$url";
        if (is_dir($dirpath)) {
            foreach (scandir($dirpath) as $fontname) {
                $fontdirurl = "$url/$fontname/css";
                if ($fontname != '.' && $fontname != '..' && is_dir("$documentRoot/$fontdirurl")) {
                    $fonturl = "$fontdirurl/$fontname.css";
                    $fonturlmin = "$fontdirurl/$fontname.min.css";
                    $furl = NULL;
                    if (file_exists($documentRoot.'/'.$fonturlmin)) {
                        $furl = $fonturlmin;
                    } elseif (file_exists($documentRoot.'/'.$fonturl)) {
                        $furl = $fonturl;
                    }
                    if (!is_null($furl)) {
                        $tags[] = "<link rel='stylesheet' href='{$furl}' />";
                    }
                }
            }
        }
        return $tags;
    }

    /**
    *
    * Description:
    *   This function Obtain all name files into of a directory where $type is the extension of the file
    *
    * Example:
    *   $array = obtainFiles('/var/www/html/modules/calendar/themes/default/js/','js');
    *
    * Developer:
    *   Eduardo Cueva
    *
    * e-mail:
    *   ecueva@palosanto.com
    */
    private function obtainFiles($dir, $type)
    {
        $files = glob("{$dir}/*.{$type}");
        if (!is_array($files)) return array();
        return array_map('basename', $files);
    }
}
?>
