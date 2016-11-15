<?php
/**
 * Garp_Content_Export_Pdf
 * Export content in PDF format
 *
 * @package Garp_Content_Export
 * @author Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Content_Export_Pdf extends Garp_Content_Export_Html {
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = 'pdf';

    /**
     * Format a recordset
     *
     * @param Garp_Model $model
     * @param array $rowset
     * @return string
     */
    public function format(Garp_Model $model, array $rowset) {
        $html = parent::format($model, $rowset);
        $dompdf = new Dompdf\Dompdf();
        $dompdf->load_html($html);
        $dompdf->set_paper('a4', 'portrait');
        $dompdf->render();
        $out = $dompdf->output();
        return $out;
    }
}
