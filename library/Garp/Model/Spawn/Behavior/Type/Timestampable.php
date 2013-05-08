<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior_Type_Timestampable extends Garp_Model_Spawn_Behavior_Type_Abstract {
	protected $_fields = array(
		'created' => array(
			'type' => 'datetime',
			'editable' => false
		),
		'modified' => array(
			'type' => 'datetime',
			'editable' => false
		)
	);
}