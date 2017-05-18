<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.4-1                                                |
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

require_once("jpgraph/jpgraph.php");
require_once("jpgraph/jpgraph_line.php");
require_once("jpgraph/jpgraph_pie.php");
require_once("jpgraph/jpgraph_pie3d.php");
require_once("jpgraph/jpgraph_bar.php");
require_once("jpgraph/jpgraph_canvas.php");
require_once("jpgraph/jpgraph_canvtools.php");

/**
 * Método que sirve de reemplazo al mecanismo de paloSantoGraph y paloSantoGraphLib
 * para generar gráficos a partir de una clase que devuelve datos.
 *
 * @param   string  $module     Módulo que contiene la clase fuente de datos
 * @param   string  $class      Clase a instanciar para obtener fuente de datos
 * @param   string  $function   Método a llamar en la clase para obtener datos
 * @param   array   $arrParameters  Lista de parámetros para la invocación 
 * @param   string  $functionCB 
 */
function displayGraph($G_MODULE, $G_CLASS, $G_FUNCTION, $G_PARAMETERS, $G_FUNCTIONCB="")
{

if($G_MODULE != ''){
    require_once("modules/$G_MODULE/libs/$G_CLASS.class.php");//lib del modulo
    require_once("modules/$G_MODULE/configs/default.conf.php");//archivo configuracion del modulo
    global $arrConfModule;

    $dsn = isset($arrConfModule["dsn_conn_database"])?$arrConfModule["dsn_conn_database"]:"";
}
else{
    require_once("libs/$G_CLASS.class.php");//lib del modulo
    require_once("configs/default.conf.php");//archivo configuracion del modulo
    global $arrConf;

    $dsn = isset($arrConf["dsn_conn_database"]) ? $arrConf["dsn_conn_database"] : "";
}

$oPaloClass = new $G_CLASS($dsn);
$arrParam = $G_PARAMETERS;
$result = call_user_func_array(array(&$oPaloClass, $G_FUNCTION), $arrParam );

if ($G_FUNCTIONCB != '') $result['FORMAT_CALLBACK'] = array($oPaloClass, $G_FUNCTIONCB);
return displayGraphResult($result);
}

function displayGraphResult($result)
{
global $globalCB;
$globalCB = NULL;
if (isset($result['FORMAT_CALLBACK'])) $globalCB = $result['FORMAT_CALLBACK'];

//------- PARAMETROS DEL GRAPH -------
$G_TYPE    = null;//tipo de grafica
$G_TITLE   = null;//titulo
$G_COLOR = null;//colores
$G_LABEL  = array();//etiquetas
$G_SIZE   = array();//tamaño
$G_MARGIN = array();//margen
$G_LEYEND_NUM_COLUMN = 1;
$G_LEYEND_POS = array(0.05, 0.5);//posicion de las leyendas
$_MSJ_ERROR   = null;//$_MSJ_ERROR   = "Sin mensaje ERROR";
global $_MSJ_NOTHING;//$_MSJ_NOTHING = "Sin mensaje NOTHING";
$G_YDATAS     = array();
$G_ARR_COLOR  = array();
$G_ARR_FILL_COLOR  = array();
$G_ARR_LEYEND = array();
$G_ARR_STEP   = array();
$G_SHADOW = false;
$G_LABEL_Y = null;

//ESTATICOS
$G_SCALE  = "textlin";
$G_WEIGHT = 1;

//------------------- CONTRUCCION DEL ARREGLO PARA X & Y -------------------
global $xData;
$xData = array();
$yData = array();
if( sizeof($result) != 0 )
{
    $isX_array = false;//usado en LINEPLOT, PLOT3D, BARPLOT, LINEPLOT_MULTIAXIS
    foreach( $result as $att => $arrXY )
    {
        //------------------ ATTRIBUTES ------------------
        if( $att == 'ATTRIBUTES' )
        {
            foreach( $arrXY as $key => $values )
            {
                //VARIABLES NECESARIAS
                if( $key == 'LABEL_X' )            $G_LABEL[0]  = $values;
                else if( $key == 'LABEL_Y' )       $G_LABEL[1]  = $values;
                else if( $key == 'TITLE' )         $G_TITLE  = $values;
                else if( $key == 'TYPE' )          $G_TYPE   = $values;
                else if( $key == 'SIZE' )          $G_SIZE   = explode(',', $values);
                else if( $key == 'MARGIN' )        $G_MARGIN = explode(',', $values);
                else if( $key == 'COLOR' )         $G_COLOR   = $values;
                //NO NECESARIAS
                else if( $key == 'POS_LEYEND' )     $G_LEYEND_POS = explode(',', $values);
                else if( $key == 'NUM_COL_LEYEND' ) $G_LEYEND_NUM_COLUMN = $values;
                else if( $key == 'SHADOW' )         $G_SHADOW = $values;
                
            }
        }
        //------------------- MESSAGES -------------------
        else if( $att == 'MESSAGES' )
        {
            foreach( $arrXY as $key => $values ){
                if( $key == 'ERROR' )             $_MSJ_ERROR   = $values;
                else if( $key == 'NOTHING_SHOW' ) $_MSJ_NOTHING = $values;
            }
        }
        //--------------------- GRAPH ---------------------
        else if( $att == 'DATA' )
        {
            foreach( $arrXY as $DAT_N => $MODES )
            {
                foreach( $MODES as $key => $values )
                {
                    /************************************************************/
                    if( $G_TYPE == 'lineplot' || $G_TYPE == 'barplot' || $G_TYPE == 'lineplot_multiaxis' )
                    {
                        if( $key == 'VALUES' )
                        {
                            $yData = array();
                            foreach( $values as $x => $y )
                            {
                                if( $isX_array == false ) $xData[] = $x;
                                $yData[] = $y;
                            }
                            $isX_array = ( is_array($xData) ) ? true : false;
                            $G_YDATAS[] = $yData;
                        }
                        else if( $key == 'STYLE' )
                        {
                            foreach( $values as $x => $y )
                            {
                                if( $x == 'COLOR' )             $G_ARR_COLOR[]  = $y;
                                else if( $x == 'LEYEND' )       $G_ARR_LEYEND[] = $y;
                                else if( $x == 'STYLE_STEP' )   $G_ARR_STEP[]   = $y;
                                else if( $x == 'FILL_COLOR' )   $G_ARR_FILL_COLOR[] = $y;
                            }
                        }
                    }
                    else if( $G_TYPE == 'plot3d' || $G_TYPE == 'plot3d2' )
                    {
                        if( $key == 'VALUES' )
                        {
                            foreach( $values as $x => $y )
                                $yData[] = $y;
                            $G_YDATAS[0] = $yData;
                        }
                        else if( $key == 'STYLE' )
                        {
                            foreach( $values as $x => $y ){
                                if( $x == 'COLOR' )       $G_ARR_COLOR[]  = $y;
                                else if( $x == 'LEYEND' ) $xData[] = $y;
                            }
                        }
                    }
                    else if( $G_TYPE == 'bar' || $G_TYPE == 'gauge')
                    {
                        if( $key == 'VALUES' )
                            foreach( $values as $x => $y )
                                $G_YDATAS[] = $y;
                    }
                }
            }
        }
    }
}

//*****************************************//
//      ***** ***** ***** ***** *   *      //
//      *     *   * *   * *   * *   *      //
//      * *** ***** ***** ***** *****      //
//      *   * * *   *   * *     *   *      //
//      ***** *   * *   * *     *   *      //
//*****************************************//

// L I N E P L O T
if( sizeof($G_YDATAS) >= 1 )
{
    // true no funciona porque cada cadena u otro valor que se retorne es valor "valido o verdadero"
    // y equivale a true, entonces para diferenciarlo verdaderamente se compara con 'true'
    $str = checkAttributes($G_TITLE,$G_TYPE,$G_LABEL_Y,$_MSJ_ERROR,$_MSJ_NOTHING);
    if( $str != 'true' ){ showError($str, $G_SIZE); return; }

    if( $G_TYPE == 'lineplot' )
    {
        $graph = new Graph($G_SIZE[0], $G_SIZE[1], "auto");
        
        if( $G_SHADOW ) $graph->SetShadow();

        $graph->SetScale($G_SCALE);
        $graph->SetMarginColor($G_COLOR);
        $graph->title->Set($G_TITLE);
        $graph->SetFrame(true, '#999999');
        $graph->img->SetMargin($G_MARGIN[0],$G_MARGIN[1],$G_MARGIN[2],$G_MARGIN[3]);
        $graph->img->SetAntiAliasing();
        $graph->xaxis->SetLabelFormatCallback("CallBack");
        $graph->xaxis->SetLabelAngle(90);
        $graph->xaxis->title->Set($G_LABEL[0]);
        $graph->yaxis->title->Set($G_LABEL[1]);
        $graph->xgrid->Show();        
        $graph->legend->SetFillColor("#fafafa");
        $graph->legend->Pos($G_LEYEND_POS[0], $G_LEYEND_POS[1], "right","center");
        $graph->legend->SetColumns( $G_LEYEND_NUM_COLUMN );

        $graph->legend->SetColor("#444444", "#999999");

        $arr_lineplot = array();
        foreach($G_YDATAS as $num => $yDatas)
        {
            $lineplot = new LinePlot($yDatas);
            if( $G_ARR_STEP[$num] == true )
                $lineplot->SetStepStyle();

            if( $G_ARR_FILL_COLOR[$num] == true )
                $lineplot->SetFillColor($G_ARR_COLOR[$num]);

            $lineplot->SetColor($G_ARR_COLOR[$num]);
            $lineplot->SetWeight($G_WEIGHT);
            $lineplot->SetLegend($G_ARR_LEYEND[$num]);
            $arr_lineplot[] = $lineplot;
        }

        foreach($arr_lineplot as $num => $yDatas)
            $graph->Add($yDatas);

        if( sizeof($xData) > 100)
            $graph->xaxis->SetTextTickInterval( (int)(sizeof($xData)/10) );

        $graph->Stroke();
    }
    else if( $G_TYPE == 'plot3d' )
    {
        $graph = new PieGraph($G_SIZE[0], $G_SIZE[1],"auto");

        if( $G_SHADOW ) $graph->SetShadow();

        $dataMarginColor = isset($result["ATTRIBUTES"]["MARGIN_COLOR"])?$result["ATTRIBUTES"]["MARGIN_COLOR"]:"#999999";
        $dataSizePie     = isset($result["ATTRIBUTES"]["SIZE_PIE"])?$result["ATTRIBUTES"]["SIZE_PIE"]:"80";

        $graph->SetMarginColor($G_COLOR);
        $graph->SetFrame(true, $dataMarginColor);
        $graph->legend->Pos($G_LEYEND_POS[0], $G_LEYEND_POS[1], "right","center");
        $graph->legend->SetFillColor("#fafafa");
        $graph->legend->SetColor("#444444", "#999999");
        $graph->legend->SetShadow('gray@0.6',4);
        $graph->legend->SetColumns( $G_LEYEND_NUM_COLUMN );
        $graph->title->Set($G_TITLE);

        $pieplot3d = new PiePlot3d( $G_YDATAS[0] );
        $pieplot3d->SetSliceColors( $G_ARR_COLOR );
        $pieplot3d->SetCenter(0.4);
        $pieplot3d->SetSize($dataSizePie);
        $pieplot3d->SetAngle(45);
        $pieplot3d->SetStartAngle(45);
        $pieplot3d->value->SetColor('black');//color a los porcentages
        $pieplot3d->SetEdge('black');//da color al contorno y separacion del pastel
        $pieplot3d->SetLegends($xData);

        $graph->Add($pieplot3d);

        $graph->Stroke();
    }
	else if( $G_TYPE == 'plot3d2' )
	{
        if (!function_exists('displayGraph_draw_pie3d')) {
            function displayGraph_draw_pie3d($canvasx, $ydata, $arrcolor) {
                $canvasy = $canvasx;
                $escala = $canvasx / 320.0;
                $iAnchoPastel = 256 * $escala; $iAltoPastel = 155 * $escala;
                $iPosCentroX = 141 * $escala; $iPosCentroY = 91 * $escala;

                $thumb = imagecreatetruecolor($canvasx * 284/320, $canvasy * 250/320);
                $transparent = imagecolorallocatealpha($thumb, 200, 200, 200, 127);
                imagefill($thumb, 0, 0, $transparent);

                // Asignar colores de imagen
                $imgcolor = array();
                foreach ($arrcolor as $i => $sHtmlColor) {
                	$r = $g = $b = 0;
                    sscanf($sHtmlColor, "#%02x%02x%02x", $r, $g, $b);
                    $imgcolor[$i] = imagecolorallocate($thumb, $r, $g, $b);
                }

                $colorTexto = imagecolorallocate($thumb, 0, 0, 0);

                // Mostrar el gráfico de pastel
                if (!function_exists('displayGraph_pie')) {
                    function displayGraph_pie($thumb, $x, $y, $w, $h, $ydata, $G_ARR_COLOR, $colorTexto)
                    {
                        $iTotal = array_sum($ydata);
                        $iFraccion = 0;
                        $etiquetas = array();
                        for ($i = 0; $i < count($ydata); $i++) {
                            if ($ydata[$i] >= $iTotal) {
                                imagefilledellipse($thumb, $x, $y, $w, $h,
                                    $G_ARR_COLOR[$i]);
                            } else {
                                $degInicio = 360 - 45 - (int)(360.0 * ($iFraccion + $ydata[$i]) / $iTotal);
                                $degFinal = 360 - 45 - (int)(360.0 * $iFraccion / $iTotal);
                                imagefilledarc($thumb, $x, $y, $w, $h,
                                    $degInicio, $degFinal,
                                    $G_ARR_COLOR[$i],
                                    IMG_ARC_PIE);
                            }
                            $iFraccion += $ydata[$i];

                            $degMitad = ($degInicio + $degFinal) / 2;
                            $iPosTextoX = $x + 0.5 * ($w / 2.0) * cos(deg2rad($degMitad));
                            $iPosTextoY = $y + 0.5 * ($h / 2.0) * sin(deg2rad($degMitad));
                            $etiquetas[] = array($iPosTextoX, $iPosTextoY, sprintf('%.1f %%', 100.0 * $ydata[$i] / $iTotal));
                        }
/*
                        if (!is_null($colorTexto)) {
                            for ($i = 0; $i < count($ydata); $i++)
                                imagestring($thumb, 5, $etiquetas[$i][0], $etiquetas[$i][1], $etiquetas[$i][2], $colorTexto);
                        }
*/                        
                    }
                }
                for ($i = (int)(60  * $escala); $i > 0; $i--) {
                    displayGraph_pie($thumb, $iPosCentroX, $iPosCentroY + $i,
                        $iAnchoPastel, $iAltoPastel, $ydata, $imgcolor, NULL);
                }
                displayGraph_pie($thumb, $iPosCentroX, $iPosCentroY,
                    $iAnchoPastel, $iAltoPastel, $ydata, $imgcolor, $colorTexto);

                imagealphablending($thumb, true);
                imagesavealpha($thumb, true);

                $source2 = imagecreatefrompng("images/pie_alpha.png");
                imagealphablending($source2, true);
                imagecopyresampled($thumb, $source2, 0, 0, 0, 0, 290 * $escala, 294 * $escala, 290, 294);

                header("Content-Type: image/png");
                imagepng($thumb);
            }
        }
        displayGraph_draw_pie3d($G_SIZE[0], $G_YDATAS[0], $G_ARR_COLOR);
	}
    else if( $G_TYPE == 'barplot' )
    {
        $graph = new Graph($G_SIZE[0], $G_SIZE[1], "auto");

        if( $G_SHADOW ) $graph->SetShadow();

        $graph->SetScale($G_SCALE);
        $graph->SetMarginColor($G_COLOR);
        $graph->img->SetMargin($G_MARGIN[0],$G_MARGIN[1],$G_MARGIN[2],$G_MARGIN[3]);
        $graph->title->Set($G_TITLE);
        $graph->xaxis->title->Set($G_LABEL[0]);
        $graph->xaxis->SetLabelFormatCallback("CallBack");
        $graph->xaxis->SetLabelAngle(90);
        //$graph->xaxis->SetTickLabels($xData);
        $graph->yaxis->title->Set($G_LABEL[1]);
        $graph->legend->SetFillColor("#fafafa");
        $graph->legend->Pos($G_LEYEND_POS[0], $G_LEYEND_POS[1], "right","center");
        $graph->legend->SetColumns( $G_LEYEND_NUM_COLUMN );

        $arr_barplot = array();
        foreach($G_YDATAS as $num => $yDatas)
        {
            $barplot = new BarPlot($yDatas);
            $barplot->SetFillColor($G_ARR_COLOR[$num]);
            $barplot->SetLegend($G_ARR_LEYEND[$num]);
            $arr_barplot[] = $barplot;
        }

        $gbarplot = new GroupBarPlot($arr_barplot);
        $gbarplot->SetWidth(0.6);
        $graph->Add($gbarplot);

        $graph->Stroke();
    }
    else if( $G_TYPE == 'lineplot_multiaxis' )
    {
        $graph = new Graph($G_SIZE[0], $G_SIZE[1], "auto");

        if( $G_SHADOW ) $graph->SetShadow();

        $inc = sizeof($G_YDATAS);

        $graph->SetScale($G_SCALE);
        $graph->SetFrame(true, '#999999');
        $graph->title->Set($G_TITLE);
        $graph->img->SetAntiAliasing();
        $graph->xaxis->SetLabelFormatCallback("CallBack");
        $graph->img->SetMargin($G_MARGIN[0],$G_MARGIN[1],$G_MARGIN[2],$G_MARGIN[3]);
        $graph->SetMarginColor($G_COLOR);
        $graph->legend->SetFillColor("#fafafa");
        $graph->legend->Pos($G_LEYEND_POS[0], $G_LEYEND_POS[1], "right","center");
        $graph->xaxis->SetLabelAngle(90);
        $graph->legend->SetColor("#444444", "#999999");
        $graph->legend->SetShadow('gray@0.6',4);
        $graph->legend->SetColumns( $G_LEYEND_NUM_COLUMN );
        
        foreach($G_YDATAS as $num => $yData){
            $lineplot = new LinePlot($yData);
            $lineplot->SetWeight($G_WEIGHT);
            $lineplot->SetLegend($G_ARR_LEYEND[$num]);

            if( $G_ARR_STEP[$num] == true )
                $lineplot->SetStepStyle();

            if( $G_ARR_FILL_COLOR[$num] == true )
                $lineplot->SetFillColor($G_ARR_COLOR[$num]);

            if( $num == 0 ){
                $lineplot->SetColor( $G_ARR_COLOR[$num] );
                $graph->yaxis->SetColor( $G_ARR_COLOR[$num] );
                $graph->Add($lineplot);
            }
            else{
                $lineplot->SetColor( $G_ARR_COLOR[$num] );
                $graph->SetYScale($num-1, 'lin');
                $graph->ynaxis[$num-1]->SetColor( $G_ARR_COLOR[$num] );
                $graph->ynaxis[$num-1]->SetPosAbsDelta($G_MARGIN[1] + 49*($num-1));//mueve el eje Y
                $graph->AddY($num-1, $lineplot);
            }
        }

        if( sizeof($xData) > 100){
            //$graph->xaxis->SetTextLabelInterval( (int)(sizeof($xData)/8) );
            $graph->xaxis->SetTextTickInterval( (int)(sizeof($xData)/10) );
            //$graph->xaxis->SetTextTickInterval( 9*(int)(log(sizeof($xData))-1) );
        }

        $graph->Stroke();
    }
    else if( $G_TYPE == 'bar' )
    {
        $g = new CanvasGraph(91, 21,'auto');
        $g->SetMargin(0,0,0,0);
        $g->InitFrame();

        $xmax = 20;
        $ymax = 20;
        $scale = new CanvasScale($g);
        $scale->Set(0,$G_SIZE[0],0,$G_SIZE[1]);

        //DUBUJA LA BARRA
	$alto = $G_SIZE[1]; $ancho = $G_SIZE[0];
        $coor_x = 0;
        $coor_y = 0;
        $porcentage = $G_YDATAS[0];
        $valor = 90*(1-$porcentage);
        $g->img->Line($coor_x       , $coor_y      , $coor_x+$ancho, $coor_y);
        $g->img->Line($coor_x       , $coor_y      , $coor_x       , $coor_y+$alto);
        $g->img->Line($coor_x+$ancho, $coor_y      , $coor_x+$ancho, $coor_y+$alto);
        $g->img->Line($coor_x       , $coor_y+$alto, $coor_x+$ancho, $coor_y+$alto);

        for( $i = 0; $i < $alto; $i++){
            $g->img->SetColor( array(95-3*$i,138-3*$i,203-3*$i) );//para hacerlo 3D, degradacion
            $g->img->Line($coor_x, $coor_y+$i+1, $coor_x+$ancho-$valor-1, $coor_y+$i+1);
        }
        $g->Stroke();
    }
    else if ($G_TYPE == 'gauge')
    {
    	if (!function_exists('displayGraph_draw_gauge')) {
    		function displayGraph_draw_gauge($canvasx, $percent) {
                $escala = $canvasx / 320.0;
                $thumb = imagecreatetruecolor($canvasx * 284/320, $canvasx * 284/320);
            
                if ($percent > 100) $percent = 100.0;
                if ($percent < 0) $percent = 0.0;
                $angle = -135.0 + 270 * $percent / 100.0;   
            
                // COLORES
                $blanco = imagecolorallocate($thumb,255,255,255);
                $dred = imagecolorallocate($thumb,180,0,0);
                $lred = imagecolorallocate($thumb,100,0,0);
            
                $transparent = imagecolorallocatealpha($thumb, 200, 200, 200, 127);
                imagefill($thumb, 0, 0, $transparent);
            
                imagealphablending($thumb, true);
                imagesavealpha($thumb, true);  
            
                $source = imagecreatefrompng("images/gauge_base.png");
                imagealphablending($source, true);
                imagecopyresampled($thumb, $source, 0, 0, 0, 0, 285 * $escala, 285 * $escala, 285, 285);
            
                $radius = 100 * $escala;
                $radius_min = 12 * $escala;
                $centrox = 142 * $escala; $centroy = 141 * $escala;
                $x1 = $centrox + sin(deg2rad($angle)) * $radius; // x coord farest
                $x2 = $centrox + sin(deg2rad($angle-90)) * $radius_min;
                $x3 = $centrox + sin(deg2rad($angle+90)) * $radius_min;
            
                $y1 = $centroy - cos(deg2rad($angle)) * $radius;
                $y2 = $centroy - cos(deg2rad($angle-90)) * $radius_min;
                $y3 = $centroy - cos(deg2rad($angle+90)) * $radius_min;
            
                $arrTriangle1 = array($centrox, $centroy, $x1, $y1, $x2, $y2);
                $arrTriangle2 = array($centrox, $centroy, $x1, $y1, $x3, $y3);
            
                imagefilledpolygon($thumb, $arrTriangle1, 3, $lred);
                imagefilledpolygon($thumb, $arrTriangle2, 3, $dred);
            
                $source2 = imagecreatefrompng("images/gauge_center.png");
                imagealphablending($source2, true);
                imagecopyresampled($thumb, $source2, 121 * $escala, 120 * $escala, 0, 0, 44 * $escala, 44 * $escala, 44, 44);

                header("Content-Type: image/png");
                imagepng($thumb);
    		}
    	}
        displayGraph_draw_gauge($G_SIZE[0], $G_YDATAS[0] * 100.0);
    }
    else if( $G_TYPE == 'bar2' )
    {
        $alto = 20; $ancho = 90;
        $coor_x = 100;
        $coor_y = 10;
        $porcentage = 0.67;
        $valor = 90*(1-$porcentage);

        $g = new CanvasGraph($G_LEN_X, 40,'auto');
        $g->SetMargin(1,1,31,9);
        $g->SetMarginColor('#fafafa');
        $g->SetColor(array(250,250,250));
        
        $g->InitFrame();

        $xmax = 20;
        $ymax = 20;
        $scale = new CanvasScale($g);
        $scale->Set(0,$G_LEN_X,0,$G_LEN_Y);

        //DUBUJA LA BARRA
        $g->img->Line($coor_x       , $coor_y      , $coor_x+$ancho, $coor_y);
        $g->img->Line($coor_x       , $coor_y      , $coor_x       , $coor_y+$alto);
        $g->img->Line($coor_x+$ancho, $coor_y      , $coor_x+$ancho, $coor_y+$alto);
        $g->img->Line($coor_x       , $coor_y+$alto, $coor_x+$ancho, $coor_y+$alto);

        for( $i = 0; $i < $alto; $i++){
            $g->img->SetColor( array(95-4*$i,138-4*$i,203-4*$i) );//para hacerlo 3D, degradacion
            $g->img->Line($coor_x, $coor_y+$i, $coor_x+$ancho-$valor-1, $coor_y+$i);
        }

        //AGREGA LABEL 1
        $txt = "Uso de CPU";
        $t = new Text($txt,10,12);
        $t->font_style = FS_BOLD;
        $t->Stroke($g->img);

        //AGREGA LABEL 2
        $txt = "67.64% used of 2,200.00 MHz";
        $t = new Text($txt,200,12);
        $t->font_style = FS_BOLD;
        $t->Stroke($g->img);

        $g->Stroke();
    }
}
else{
    showError('nothing', $G_SIZE, $G_TITLE);
}

}

function checkAttributes($G_TITLE,$G_TYPE,$G_LABEL_Y,$_MSJ_ERROR,$_MSJ_NOTHING)
{
    return true;
    $str = '';

    if( $G_TYPE == 'lineplot' || $G_TYPE == 'barplot' || $G_TYPE == 'lineplot_multiaxis' ){
        if($G_TITLE == null)      $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: TITLE" : ",TITLE" ;
        if($G_LABEL_Y == null)    $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: LABEL_Y" : ",LABEL_Y" ;
        if($_MSJ_ERROR == null)   $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: ERROR" : ",ERROR" ;
        if($_MSJ_NOTHING == null) $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: NOTHING_SHOW" : ",NOTHING_SHOW" ;
    }
    else if( $G_TYPE == 'plot3d' ){
        if($G_TITLE == null)      $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: TITLE" : ",TITLE" ;
        if($_MSJ_ERROR == null)   $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: ERROR" : ",ERROR" ;
        if($_MSJ_NOTHING == null) $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: NOTHING_SHOW" : ",NOTHING_SHOW" ;
    }
    else if( $G_TYPE == 'bar' || $G_TYPE == 'bar2' ){
    }
    else if( $G_TYPE == 'prueba' ){
        if($G_TITLE == null)      $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: TITLE" : ",TITLE" ;
        if($G_LABEL_Y == null)    $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: LABEL_Y" : ",LABEL_Y" ;
        if($_MSJ_ERROR == null)   $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: ERROR" : ",ERROR" ;
        if($_MSJ_NOTHING == null) $str .= ($str == "") ? _tr("Failure in")." ATTRIBUTE: NOTHING_SHOW" : ",NOTHING_SHOW" ;
    }
    else
        $str = _tr("Failure in")." ATTRIBUTE: TYPE";

    // true no funciona, retorno mejor 'true'
    if( $str == '' ) return 'true';
    return $str;
}

function showError($msj, $G_SIZE = array(400,300), $G_TITLE = "")
{
    $graph = new CanvasGraph($G_SIZE[0],$G_SIZE[1],"auto");

    if($msj == 'nothing'){
        Global $_MSJ_NOTHING;
        $titulo = utf8_decode($_MSJ_NOTHING);
        $title = new Text($G_TITLE);
        $title->ParagraphAlign('center');
        $title->SetFont(FF_FONT2,FS_BOLD);
        $title->SetMargin(3);
        $title->SetAlign('center');
        $title->Center(0,$G_SIZE[0],$G_SIZE[1]/2);
        $graph->AddText($title);
    }
    else{
        $titulo = utf8_decode($msj);
    }

    $t1 = new Text( $titulo );
    $t1->SetBox("white","black",true);
    $t1->ParagraphAlign("center");
    $t1->SetColor("black");

    $graph->AddText($t1);
    $graph->img->SetColor('navy');
    $graph->img->SetTextAlign('center','bottom');       
    $graph->img->Rectangle(0,0,$G_SIZE[0]-1,$G_SIZE[1]-1);
    $graph->Stroke();
}

function CallBack($value)
{
    global $xData;
    global $globalCB;
    $v = $xData[$value];
    if (!is_null($globalCB)) $v = call_user_func($globalCB, $v);
    return $v;
}

?>
