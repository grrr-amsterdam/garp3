<?php
/**
 * Garp_Spawn_Behavior_Type_Timestampable
 * class description
 *
 * @package Garp_Spawn_Behavior_Type
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Behavior_Type_Timestampable extends Garp_Spawn_Behavior_Type_Abstract {
    const DEFAULT_FIELDS = [
        'created' => [
            'type' => 'datetime',
            'editable' => false,
            'required' => false
        ],
        'modified' => [
            'type' => 'datetime',
            'editable' => false,
            'required' => false
        ]
    ];

    public function getFields() {
        $params = $this->getParams();
        $created = $params['createdField'] ?? 'created';
        $modified = $params['modifiedField'] ?? 'modified';
        return [
            $created => self::DEFAULT_FIELDS['created'],
            $modified => self::DEFAULT_FIELDS['modified'],
        ];
    }
}

