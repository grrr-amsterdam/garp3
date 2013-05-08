<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior_Factory {
	const BEHAVIOR_NAMESPACE = 'Garp_Model_Spawn_Behavior_Type_';


	public function produce(Garp_Model_Spawn_Model_Abstract $model, $origin, $name, $params = null, $behaviorType = null) {
		$behaviorClass = $this->_getBehaviorClass($name);
		$behavior = new $behaviorClass($model, $origin, $name, $params, $behaviorType);
		
		return $behavior;
	}

	protected function _getBehaviorClass($name) {
		$hasOwnClass = $this->_hasOwnClass($name);
		$class = self::BEHAVIOR_NAMESPACE;
		$class .= $hasOwnClass ? $name : 'Generic';

		return $class;
	}

	protected function _hasOwnClass($name) {
		$exists = @class_exists(self::BEHAVIOR_NAMESPACE . $name);
		return $exists;
	}

}