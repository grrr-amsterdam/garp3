<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Draftable extends Garp_Spawn_Behavior_Type_Abstract {
	protected $_fields = array(
		'published' => array(
			'type' => 'datetime',
			'editable' => true,
			'required' => false
		),
		'online_status' => array(
			'type' => 'checkbox',
			'editable' => true,
			'default' => 1,
			'required' => false
		)
	);
}