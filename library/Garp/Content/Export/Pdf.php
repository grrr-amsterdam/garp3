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
        /**
         * FIXME: Dompdf fails when the locale is set to 'nl_NL' (which is the Garp default).
         * This issue is known (see http://code.google.com/p/dompdf/issues/detail?id=20), so
         * hopefully it will be fixed in the future. For the time being, we just set the locale
         * to en_US.
         */
        $prevLocale = setlocale(LC_ALL, 0);
        setlocale(LC_ALL, null); // <-- return to system default

        $html = parent::format($model, $rowset);
        $dompdf = new Dompdf\Dompdf();
        $dompdf->load_html($html);
        $dompdf->set_paper('a4', 'portrait');
        $dompdf->render();
        $out = $dompdf->output();

        // return locale to previous setting
        setlocale(LC_ALL, $prevLocale);

        return $out;
    }
}
