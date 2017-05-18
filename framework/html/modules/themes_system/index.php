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
  $Id: new_campaign.php $ */

require_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoGrid.class.php";

/*
  BASE SETTINGS
CREATE TABLE settings 
(
    key varchar(32), 
    value varchar(32)
);
*/

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    require_once "modules/$module_name/libs/PaloSantoThemes.class.php";
    
    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
        
    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // se conecta a la base
    $pDB = new paloDB($arrConf['elastix_dsn']['settings']);
    if(!empty($pDB->errMsg)) {
        $smarty->assign("mb_message", _tr("Error when connecting to database")."<br/>".$pDB->errMsg);
    }

    // Definición del formulario de nueva campaña
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CHANGE", _tr("Save"));
    $smarty->assign("icon","modules/$module_name/images/system_preferences_themes.png");

    $oThemes = new PaloSantoThemes($pDB); 
    $arr_themes = $oThemes->getThemes("$base_dir/themes/");

    $formCampos = array(
        'themes'                 => array(
            "LABEL"                  => _tr("Themes"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arr_themes,
            "VALIDATION_TYPE"        => "",
            "VALIDATION_EXTRA_PARAM" => "",
        )
    );
    $oForm = new paloForm($smarty, $formCampos);

    if (isset($_POST['changeTheme'])) {
        $contenidoModulo = updateTheme($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    } else {
        $contenidoModulo = changeTheme($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm);
    }
    return $contenidoModulo;
}

function changeTheme($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {

    $oThemes = new PaloSantoThemes($pDB); 
    $tema_actual = $oThemes->getThemeActual(); 
    $arrTmp['themes']   = $tema_actual;
    $smarty->assign("icon","modules/$module_name/images/system_preferences_themes.png");
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/new.tpl", _tr("Change Theme"),$arrTmp);
    return $contenidoModulo;
}

function updateTheme($pDB, $smarty, $module_name, $local_templates_dir, $formCampos, $oForm) {

    if(!$oForm->validateForm($_POST)) {
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores=$oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
        }
        $strErrorMsg .= "";
        $smarty->assign("mb_message", $strErrorMsg);
    } else {
        $oThemes = new PaloSantoThemes($pDB);
        $exito   = $oThemes->updateTheme($_POST['themes']);

        if ($exito) {
            if($oThemes->smartyRefresh($_SERVER['DOCUMENT_ROOT'])){
		header("Location: ?menu=$module_name");
		die();
	    }
	    else{
		$smarty->assign("mb_title", _tr("ERROR"));
		$smarty->assign("mb_message", _tr("The smarty cache could not be deleted"));
	    }
        } else {
            $smarty->assign("mb_title", _tr("Validation Error"));
            $smarty->assign("mb_message", $oThemes->errMsg);
        } 
    }
    $smarty->assign("icon","modules/$module_name/images/system_preferences_themes.png");
    $contenidoModulo = $oForm->fetchForm("$local_templates_dir/new.tpl", _tr("Change Theme"),$_POST);
    return $contenidoModulo;
}
?>
