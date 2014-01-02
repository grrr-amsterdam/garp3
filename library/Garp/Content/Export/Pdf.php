<?php
/**
 * Garp_Content_Export_Pdf
 * Export content in PDF format
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Export_Pdf extends Garp_Content_Export_Html {
	/**
	 * File extension
	 * @var String
	 */
	protected $_extension = 'pdf';
	
	
	/**
	 * Format a recordset
	 * @param Garp_Model $model
	 * @param Array $rowset
	 * @return String
	 */
	protected function _format(Garp_Model $model, array $rowset) {
		require APPLICATION_PATH.'/../garp/library/Garp/3rdParty/dompdf/dompdf_config.inc.php';
		
		/**
		 * FIXME: Dompdf fails when the locale is set to 'nl_NL' (which is the Garp default).
		 * This issue is known (see http://code.google.com/p/dompdf/issues/detail?id=20), so 
		 * hopefully it will be fixed in the future. For the time being, we just set the locale
		 * to en_US.
		 */
		$prevLocale = setlocale(LC_ALL, 0);
		setlocale(LC_ALL, null); // <-- return to system default
		
		$loader = new Garp_Util_Loader(DOMPDF_INC_DIR);
		$loader->setFileExtension('.cls.php')
			   ->setFolderSeparator('*')	// folder separator not used in this case
			   ->addFilter('lowercase', function($s) {
			return mb_strtolower($s);
		})->register();

		$html = parent::_format($model, $rowset);		
		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->set_paper('a4', 'portrait');
		$dompdf->render();		
		$out = $dompdf->output();
		
		// return locale to previous setting
		setlocale(LC_ALL, $prevLocale);
		
		return $out;
	}
}