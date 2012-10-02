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
		include_once APPLICATION_PATH.'/../garp/library/Garp/3rdParty/dompdf/dompdf_config.inc.php';

		/**
		 * FIXME: Dompdf fails when the locale is set to 'nl_NL' (which is the Garp default).
		 * This issue is known (see http://code.google.com/p/dompdf/issues/detail?id=20), so 
		 * hopefully it will be fixed in the future. For the time being, we just set the locale
		 * to en_US.
		 */
		$prevLocale = setlocale(LC_ALL, 0);
		setlocale(LC_ALL, null); // <-- return to system default
		
		/**
 		 * DOMPDF has its own autoloader. checkIfFileExists(true) allows our 
 		 * loader to be chainable. That way the DOMPDF autoloader will take over 
 		 * when our loader cannot find the class.
 		 */
		$loader = Garp_Util_Loader::getInstance();
		$loader->checkIfFileExists(true);

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
