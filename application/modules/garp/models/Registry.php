<?php
/**
 * G_Model_Registry
 * Model containing various project-specific configuration values.
 * Look at it like a dynamic, managable application.ini.
 * An example of the data it might contain would be the company address.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_Registry extends Garp_Model_Db {
	/**
	 * Table name
	 * @var String
	 */
	protected $_name = '_garp_registry';
	
	
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable());
		parent::init();
	}
}