<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior_Type_Locatable extends Garp_Model_Spawn_Behavior_Type_Abstract {
	protected $_fields = array(
		'location_lat' => array(
			'type' => 'numeric',
			'float' => true,
			'unsigned' => false,
			'required' => false,
			'editable' => true,
			'visible' => false
		),
		'location_long' => array(
			'type' => 'numeric',
			'float' => true,
			'unsigned' => false,
			'required' => false,
			'editable' => true,
			'visible' => false
		)
	);
	
	/**
	 * @return 	Bool 	Whether this behavior needs to be registered with an observer
	 * 					called in the PHP model's init() method
	 */
	public function needsPhpModelObserver() {
		return false;
	}
}