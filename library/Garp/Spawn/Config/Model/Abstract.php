<?php
/**
 * A complete model configuration scheme with defaults.
 * @author David Spreekmeester | grrr.nl
 */
abstract class Garp_Spawn_Config_Model_Abstract extends ArrayObject {
	protected $_defaults = array(
		'order' => "created DESC",
		'creatable' => true,
		'deletable' => true,
		'quickAddable' => false,
		'visible' => true,
		'listFields' => array(),
		'module' => 'default',
		'relations' => array(),
		'behaviors' => array()
	);


	public function __construct(
		$id,
		Garp_Spawn_Config_Storage_Interface $storage,
		Garp_Spawn_Config_Format_Interface $format
	) {
		$this['id'] = $id;
		$rawConfig = $storage->load($id);

		$config = $format->parse($id, $rawConfig);
		$this->_setArrayProperties($config);
		
		$this->_addStaticDefaults();
		$this->_addModelLabel($id);
	}
	
	
	protected function _setArrayProperties(array $config) {
		foreach ($config as $key => $val) {
			$this[$key] = $val;
		}
	}


	protected function _addStaticDefaults() {
		foreach ($this->_defaults as $prop => $defaultValue) {
			if (
				!array_key_exists($prop, $this) ||
				is_null($this[$prop])
			) {
				$this[$prop] = $defaultValue;
			}
		}
	}
	
	protected function _addModelLabel($id) {
		$defaults = array(
			'label' => ucfirst(Garp_Spawn_Util::underscored2readable(
				Garp_Spawn_Util::camelcased2underscored($this['id'])
			))
		);

		foreach ($defaults as $prop => $defaultValue) {
			if (
				!array_key_exists($prop, $this) ||
				is_null($this[$prop])
			) {
				$this[$prop] = $defaultValue;
			}
		}
	}
}