<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Behavior_Type_Authorable extends Garp_Spawn_Behavior_Type_Abstract {	
	/**
	 * @return 	Bool 	Whether this behavior needs to be registered with an observer
	 * 					called in the PHP model's init() method
	 */
	public function needsPhpModelObserver() {
		$model = $this->getModel();
		
		return !$model->isTranslated();
	}
}
