<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Behavior_Type_Sluggable extends Garp_Model_Spawn_Behavior_Type_Abstract {
	const SLUG_FIELD_PARAM = 'slugField';

	/**
	 * @var Array $_defaultParams
	 */
	protected $_defaultParams = array(
		'baseField' => 'name',
		'slugField' => 'slug'
	);

	protected $_slugFieldConfig = array(
		'type' => 'text',
		'maxLength' => 255,
		'editable' => false,
		'unique' => true,
		'required' => false
	);		



	public function getFields() {
		$slugFieldName 		= $this->_getSlugFieldName();
		$slugFieldConfig 	= $this->_getSlugFieldConfig();
		$fields 			= array($slugFieldName => $slugFieldConfig);

		return $fields;
	}
	
	public function getParams() {
		$params 		= parent::getParams();
		$defaultParams 	= $this->getDefaultParams();
		
		foreach ($defaultParams as $paramName => $paramValue) {
			if (!array_key_exists($paramName, $params)) {
				$params[$paramName] = $paramValue;
			}
		}

		return $params;
	}
	
	/**
	 * @return Array
	 */
	public function getDefaultParams() {
		return $this->_defaultParams;
	}
	
	/**
	 * @return	Array	Configuration of the slug field
	 */
	protected function _getSlugFieldConfig() {
		return $this->_slugFieldConfig;
		
		/**
		 * @todo: multilingual al dan niet toevoegen.
		 */
	}

	/**
	 * @return	Array	Configuration of the slug field
	 */
	protected function _getSlugFieldName() {
		$params = $this->getParams();
		return $params[self::SLUG_FIELD_PARAM];
	}
	
}