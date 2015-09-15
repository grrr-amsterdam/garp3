<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Factory {
	const BEHAVIOR_NAMESPACE = 'Garp_Spawn_Behavior_Type_';


	public function produce(Garp_Spawn_Model_Abstract $model, $origin, $name, $params = null, $behaviorType = null, $behaviorModule = null) {
		$behaviorClass = $this->_getBehaviorClass($name);
		return new $behaviorClass($model, $origin, $name, $params, $behaviorType, $behaviorModule);
	}

	protected function _getBehaviorClass($name) {
		$hasOwnClass = $this->_hasOwnClass($name);
		$class = self::BEHAVIOR_NAMESPACE .
			($hasOwnClass ? $name : 'Generic');

		return $class;
	}

	protected function _hasOwnClass($name) {
		return @class_exists(self::BEHAVIOR_NAMESPACE . $name);
	}

	/**
 	 * Check wether a behavior should be spawned on an i18n model.
 	 * This used to always be true, but could use better validation. For now we'll make do with
 	 * validation on the Sluggable behavior, that actually produces errors in some cases when
 	 * spawned on an i18n model erroneously.
 	 */
	public static function isI18nBehavior($name, $config, $inputs) {
		if ($name === 'Sluggable') {
			return self::_shouldSluggableBeSpawnedOnI18nModel($config, $inputs);
		}
		return true;
	}

	protected static function _shouldSluggableBeSpawnedOnI18nModel($config, $inputs) {
		$baseFields = !empty($config['baseField']) ? (array)$config['baseField'] : array('name');
		$multilingualBaseFields = array_filter($baseFields, function($field) use ($inputs) {
			return
				is_string($field) &&
				array_key_exists($field, $inputs) &&
				$inputs[$field]['multilingual']
			;
		});
		return count($multilingualBaseFields) > 0;
	}
}
