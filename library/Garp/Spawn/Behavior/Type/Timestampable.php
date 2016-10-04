<?php
/**
 * Garp_Spawn_Behavior_Type_Timestampable
 * class description
 *
 * @package Garp_Spawn_Behavior_Type
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_Timestampable extends Garp_Spawn_Behavior_Type_Abstract {
    protected $_fields = array(
        'created' => array(
            'type' => 'datetime',
            'editable' => false,
            'required' => false
        ),
        'modified' => array(
            'type' => 'datetime',
            'editable' => false,
            'required' => false
        )
    );
}
