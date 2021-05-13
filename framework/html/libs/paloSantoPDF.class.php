<?php
require_once("tcpdf/tcpdf.php");
define ('K_TCPDF_EXTERNAL_CONFIG', 'tcpdf/fonts/');

class paloSantoPDF extends TCPDF
{
    /* Fuente a usar para todo el reporte. Requiere un archivo llamado
     * tcpdf/fonts/FONTNAME.php para que se pueda hacer uso. */
    var $elxReportFontFamily = 'helvetica'/*'Verdana'*/;

    var $elxStyles = array(
        'PageHeader'    =>  array(
            'FontColor' =>  array(255, 255, 255),
            'FillColor' =>  array(5, 68, 132),
            'FontSize'  =>  18,
        ),
        'PageFooter'    =>  array(
            'FontColor' =>  array(128, 128, 128),
            'FillColor' =>  array(255, 255, 255),
            'FontSize'  =>  9,
        ),
        'TableHeader'   =>  array(
            'FontColor' =>  array(255, 255, 255),
            'FillColor' =>  array(227, 83, 50),
            'FontSize'  =>  11,
        ),
        'TableData'     =>  array(
            'FontColor' =>  array(0, 0, 0),
            'FillColor' =>  array(244, 244, 244),
            'FontSize'  =>  11,
        ),
    );

    private $_elxReportTitle = '';
    private $_anchoCols = array();

    private function _switchElxRenderStyle($k)
    {
        $style = $this->elxStyles[$k];
        $this->SetTextColor($style['FontColor'][0], $style['FontColor'][1], $style['FontColor'][2]);
        $this->SetFillColor($style['FillColor'][0], $style['FillColor'][1], $style['FillColor'][2]);
        $this->SetFontSize($style['FontSize']);
    }

    // Pintar la banda azul con el título del reporte
    function Header()
    {
        $this->_switchElxRenderStyle('PageHeader');

        // Cálculo heredado de implementación anterior
        // $this->w es el ancho de la página actual
        $tam = $this->w - 3;
        $pX = ($this->w - $tam) / 2;
        $this->SetX($pX);

        // Dibujar el título alineado a la derecha, con relleno
        $this->Cell($tam, 15, $this->_elxReportTitle, 0, 0, 'R', true);
    }

    // Pintar el número de página
    function Footer()
    {
        $this->_switchElxRenderStyle('PageFooter');
        $this->SetY(-15);   // Posición a 1,5 cm del final

        // Dibujar el contador centrado, sin relleno
        $this->Cell(0, 10, _tr('Page').' '.$this->getPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
    }

    // Función para iniciar la generación del reporte
    function printTable($outputFile, $title, $header, &$data)
    {
        $this->_elxReportTitle = trim($title);

        $this->SetFont($this->elxReportFontFamily);
        $this->AddPage();
        $this->SetLineWidth(0.05);
        $this->SetDrawColor(153, 153, 153); // TODO: parametrizar

        if (!(is_array($header) && is_array($data) && count($header) > 0 && count($data) > 0)) {
            $this->Cell(40, 6, _tr("No hay datos que mostrar"), 0);
        } else {
            // Sólo son necesarios los textos de los títulos
            $h = array();
            foreach ($header as $k => $head_tag) $h[$k] = $head_tag['name'];
            $header = $h;

            // Se calculan los anchos requeridos para las columnas
            $this->_switchElxRenderStyle('TableHeader');
            $this->_anchoCols = array_map(array($this, '_elxAnchoTexto'), $header);
            $this->_switchElxRenderStyle('TableData');
            for ($i = 0; $i < count($data); $i++) {
                $this->_anchoCols = array_map('max', $this->_anchoCols, array_map(array($this, '_elxAnchoTexto'), $data[$i]));
            }

            // Se verifica si el ancho total excede el ancho de la página
            $iMaxAncho = $this->getPageWidth() - 3;
            $iAnchoReq = array_sum($this->_anchoCols);
            if ($iAnchoReq > $iMaxAncho) {
                /* Algunas columnas deben reducirse. Se calcula la fracción máxima
                 * del ancho que puede dedicarse a cada columna. Toda columna cuyo
                 * ancho sea menor a la fracción acumula un exceso que puede luego
                 * repartirse entre las columnas más anchas. */
                $fraccion_col = $iMaxAncho / count($this->_anchoCols);
                $ncols_anchas = 0;
                $exceso = 0;
                foreach ($this->_anchoCols as $ancho) {
                    if ($ancho < $fraccion_col) $exceso += $fraccion_col - $ancho;
                    if ($ancho > $fraccion_col) $ncols_anchas++;
                }
                if ($ncols_anchas > 0) {
                    $nancho = ($exceso + $fraccion_col * $ncols_anchas) / $ncols_anchas;
                    for ($i = 0; $i < count($this->_anchoCols); $i++) {
                        if ($this->_anchoCols[$i] > $fraccion_col) $this->_anchoCols[$i] = $nancho;
                    }
                    $iAnchoReq = array_sum($this->_anchoCols);
                }
            }
            $x_inicial = ($this->getPageWidth() - $iAnchoReq) / 2; // centrado de tabla
            $y_inicial = 20;

            $this->SetXY($x_inicial, $y_inicial);
            $this->_switchElxRenderStyle('TableHeader');
            $this->_outputElxRow($header, TRUE, 'C');
            $this->_switchElxRenderStyle('TableData');
            $zebra_fill = FALSE;
            foreach ($data as &$row) {
                $altura_fila = $this->_calcularAlturaFila($row);
                if ($this->CheckPageBreak($altura_fila, '', FALSE)) {
                    $this->CheckPageBreak($altura_fila, '', TRUE);

                    $this->SetXY($x_inicial, $y_inicial);
                    $this->_switchElxRenderStyle('TableHeader');
                    $this->_outputElxRow($header, TRUE, 'C');
                    $this->_switchElxRenderStyle('TableData');
                }
                $this->_outputElxRow($row, $zebra_fill, 'J');
                $zebra_fill = !$zebra_fill;
            }
        }

        $this->Output($outputFile, 'D');
    }

    private function _elxAnchoTexto($s) { return $this->GetStringWidth($s) + 3; }

    private function _calcularAlturaFila(&$row)
    {
        return max(array_map(array($this, 'getStringHeight'), $this->_anchoCols, $row));
    }

    private function _outputElxRow(&$row, $fill, $align)
    {
        $h = $this->_calcularAlturaFila($row);
        $x_orig = $this->GetX();
        $y = $this->GetY();
        for ($i = 0; $i < count($row); $i++) {
            $x = $this->GetX();
            $w = $this->_anchoCols[$i];
            $this->Rect($x, $y, $w, $h, $fill ? 'DF' : '');
            $this->MultiCell($w, 5, rtrim($row[$i]), 0, $align);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
        $this->SetX($x_orig);
    }
}
?>