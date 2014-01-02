<?php
/**
 * Garp_Content_Export_Abstract
 * Blueprint for content exporters
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
abstract class Garp_Content_Export_Abstract {
	/**
	 * File extension
	 * @var String
	 */
	protected $_extension = '';
	
	
	/**
	 * Return the bytes representing the export format (for instance, binary code 
	 * describing a PDF or Excel file). These will be offered to download.
	 * @param Garp_Util_Configuration $params Various parameters describing which content to export
	 * @return String
	 */
	public function getOutput(Garp_Util_Configuration $params) {
		if (array_key_exists('filter', $params) && $params['filter']) {
			$filter = Zend_Json::decode($params['filter']);
		} else {
			$filter = array();
		}
		
		$fetchOptions = array('query'  => $filter);
		if (!empty($params['fields'])) {
			$fields = Zend_Json::decode($params['fields']);
			$fetchOptions['fields'] = $fields;
		}
		
		if (isset($params['sortField']) && isset($params['sortDir'])) {
			$fetchOptions['sort'] = array($params['sortField'].' '.$params['sortDir']);
		}
			
		switch ($params['selection']) {
			case 'id':
				$params->obligate('id');
				$filter['id'] = $params['id'];
			break;
			case 'page':
				$params->obligate('pageSize');
				// specific page
				if (isset($params['page'])) {
					$fetchOptions['start'] = (($params['page']-1)*$params['pageSize']);
					$fetchOptions['limit'] = $params['pageSize'];
				// specific selection of pages
				} elseif (isset($params['from']) && isset($params['to'])) {
					$pages = ($params['to'] - $params['from'])+1;
					$fetchOptions['start'] = (($params['from']-1)*$params['pageSize']);
					$fetchOptions['limit'] = ($pages*$params['pageSize']);
				} else {
					throw new Garp_Content_Exception('Invalid paging configuration given. Possible options: "page", "from", "to".');
				}
			break;
		}
		
		$className	= Garp_Content_Api::modelAliasToClass($params['model']);
		$manager	= new Garp_Content_Manager($className);
		$data		= $manager->fetch($fetchOptions);
		return $this->_format($manager->getModel(), (array)$data);
	}
	
	
	/**
	 * Generate a filename for the exported text file
	 * @param Garp_Util_Configuration $params
	 * @return String 
	 */
	public function getFilename(Garp_Util_Configuration $params) {
		$className = Garp_Content_Api::modelAliasToClass($params['model']);
		$model = new $className();
		$filename  = $model->getName();
		$filename .= '_'.date('Y_m_d');
		$filename .= '.';
		$filename .= $this->_extension;
		return $filename;
	}
	
	
	/**
	 * Format a recordset
	 * @param String $model The exported model. Formatters may want additional metadata from this.
	 * @param Array $rowset
	 * @return String
	 */
	abstract protected function _format(Garp_Model $model, array $rowset);
}