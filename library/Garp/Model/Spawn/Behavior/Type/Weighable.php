<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior_Type_Weighable extends Garp_Model_Spawn_Behavior_Type_Abstract {
	
	/**
	 * Get the parameters, but not the ones referring a HasAndBelongsToMany model.
	 */
	public function getNonHabtmParams() {
		$params	= $this->getParams();		
		$params	= array_filter($params, array($this, '_isNotHabtmRelName'));
		
		return $params;
	}
	
	protected function _isNotHabtmRelName($modelName) {
		$habtmRels		= $this->getModel()->relations->getRelations('type', 'hasAndBelongsToMany');
		$habtmRelNames 	= array_keys($habtmRels);
		$isHabtmRelName = in_array($modelName, $habtmRelNames);
		
		return !$isHabtmRelName;
	}
	
}