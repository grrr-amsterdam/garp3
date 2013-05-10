<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Config_Model_I18n extends ArrayObject {
	const I18N_MODEL_ID_POSTFIX = 'I18n';
	
	/**
	 * @var String $_parentId
	 */
	protected $_parentId;
	

	public function __construct(Garp_Spawn_Config_Model_Base $baseConfig) {
		$this->setParentId($baseConfig['id']);
		$config = $this->_deriveI18nShadowModelConfig($baseConfig);
		
		$this->setArrayProperties($config);
	}
	
	/**
	 * @return String
	 */
	public function getParentId() {
		return $this->_parentId;
	}
	
	/**
	 * @param String $parentId
	 */
	public function setParentId($parentId) {
		$this->_parentId = $parentId;
	}
	
	public function setArrayProperties(array $config) {
		foreach ($config as $key => $val) {
			$this[$key] = $val;
		}
	}
	
	/**
	 * @return 	Array 	Derived configuration for the i18n shadow model.
	 */
	protected function _deriveI18nShadowModelConfig(Garp_Spawn_Config_Model_Base $config) {
		$config = (array)$config;
		
		$this->_validate($config);
		

		$config['id'] 			.= self::I18N_MODEL_ID_POSTFIX;		
		$config 				= $this->_filterUnnecessaryFields($config);
		$config['inputs'] 		+= $this->_getI18nSpecificFields();
		$config['relations'] 	= $this->_getRelationConfigToParent();
		$config					= $this->_correctOrderProperty($config);
		
		return $config;
	}
	
	protected function _validate(array $config) {
		if (!array_key_exists('inputs', $config)) {
			throw new Exception('No inputs found in Model config for ' . $config['id']);
		}
	}
	
	protected function _filterUnnecessaryFields(array $config) {		
		$config['inputs'] = array_filter($config['inputs'], array($this, '_isI18nField'));		
		return $config;
	}
	
	protected function _isI18nField(array $fieldConfig) {
		if (
			$this->_hasFieldProp($fieldConfig, 'primary') ||
			$this->_hasFieldProp($fieldConfig, 'multilingual')
		) {
			return true;
		}
	}
	
	protected function _hasFieldProp(array $fieldConfig, $prop) {
		if (
			array_key_exists($prop, $fieldConfig) &&
			$fieldConfig[$prop]
		) {
			return true;
		}
		
		return false;
	}
	
	protected function _getRelationConfigToParent() {
		$relation = array(
			$this->getParentId() => array(
				'type' => 'belongsTo'
			)
		);
					
		return $relation;
	}

	protected function _getI18nSpecificFields() {
		$fields = array(
			'lang' => array(
				'type' => 'text',
				'maxLength' => 2
			)
		);
			
		return $fields;
	}
	
	protected function _correctOrderProperty(array $config) {
		if (
			array_key_exists('order', $config) &&
			!array_key_exists($config['order'], $config['inputs'])
		) {
			$config['order'] = 'id';
		}
		
		return $config;
	}
}
