<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Model_I18n extends Garp_Spawn_Model_Abstract {
	public function __construct(Garp_Spawn_Config_Model_I18n $config) {
		parent::__construct($config);
	}

	/**
	 * @return 	Bool 	Whether this is a i18n leaf model, derived from a multilingual base model
	 */
	public function isTranslated() {
		return true;
	}
}