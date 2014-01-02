<?php
/**
 * Garp_Model_Behavior_Bitlyable
 * Adds a virtual 'bitly_url' column.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Bitlyable extends Garp_Model_Behavior_Abstract {
	/**
	 * Name of the column containing the URL
	 * @var String
	 */
	const COLUMN_NAME = 'bitly_url';


	/**
	 * Column containing URL to shorten.
	 * @var Array
	 */
	protected $_column; 


	/**
 	 * Column containing shortened URL
 	 * @var String
 	 */
	protected $_targetColumn;


	/**
 	 * A printf() compatible string that forms the record's URL
 	 * @var String
 	 */
	protected $_url;


	/**
	 * Configuration.
	 * @return Void
	 */
	protected function _setup($config) {
		if (empty($config['column'])) {
			throw new Garp_Model_Behavior_Exception('"column" is a required parameter.');
		}
		$this->_column = $config['column'];

		if (empty($config['url'])) {
			throw new Garp_Model_Behavior_Exception('"url" is a required parameter.');
		}
		$this->_url = $config['url'];

		if (empty($config['targetColumn'])) {
			$config['targetColumn'] = self::COLUMN_NAME;
		}
		$this->_targetColumn = $config['targetColumn'];
	}


	/**
 	 * After insert callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeInsert(array &$args) {
		$data = &$args[1];
		$this->_setBitlyUrl($data);
	}


	/**
 	 * Before update callback
 	 * @param Array $args
 	 * #return Void
 	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];
		$this->_setBitlyUrl($data);
	}


	/**
 	 * Set Bit.ly URL
 	 * @param Array $data Record data passed to an update or insert call
 	 * @return Void
 	 */
	protected function _setBitlyUrl(array &$data) {
		if (!empty($data[$this->_column])) {
			$bitly = new Garp_Service_Bitly();
			$view = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('view');
			$url = sprintf($this->_url, $data[$this->_column]);
			$response = $bitly->shorten(array(
				'longUrl' => $view->fullUrl($url)
			));
			if ($response['status_code'] == 200) {
				$shortenedUrl = $response['data']['url'];
				$data[$this->_targetColumn] = $shortenedUrl;
			}
		}
	}
}
