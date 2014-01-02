<?php
/**
 * Garp_Content_Export_Html
 * Export content in HTML format
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Export_Html extends Garp_Content_Export_Abstract {
	/**
	 * File extension
	 * @var String
	 */
	protected $_extension = 'html';
	
	
	/**
	 * Format a recordset
	 * @param Garp_Model $model
	 * @param Array $rowset
	 * @return String
	 */
	protected function _format(Garp_Model $model, array $rowset) {
		$view = new Zend_View();
		$view->setScriptPath(APPLICATION_PATH.'/modules/g/views/scripts/content/export/');
		$view->data = $rowset;
		$view->name = $model->getName();
		$out = $view->render('html.phtml');
		return $out;
	}
}