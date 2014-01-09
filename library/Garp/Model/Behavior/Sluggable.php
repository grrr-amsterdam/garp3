<?php
/**
 * Garp_Model_Behavior_Sluggable
 * Generates a unique slug for a record
 *
 * @author       Harmen Janssen, David Spreekmeester | grrr.nl
 * @version      1.5
 * @package      Garp_Model_Behavior
 */
class Garp_Model_Behavior_Sluggable extends Garp_Model_Behavior_Abstract {
	/**
	 * Version separator (e.g. <base><version-separator><int>)
	 * @var String
	 */
	const VERSION_SEPARATOR = '-';

	/**#@+
 	 * Exceptions
 	 * @var String
 	 */
	const EXCEPTION_LENGTH_MISMATCH = '"baseField" and "slugField" need to have the same length when using multiple slugFields.';
	const EXCEPTION_MISSING_CONFIG = '"%s" is a required config key';
    /**#@-*/
		
	/**
	 * Configuration
	 * @var Array
	 */
	protected $_config;

	/**
	 * Make sure the config array is at least filled with some default values to work with.
	 * 'baseField' can be a string or an array. If it's a string, it's the database column on which the slug should be based.
	 * If it's an array:
	 * - if $config['slugField'] is empty or a single value:
	 * 	 A list of database columns that should be combined to base the slug on.
	 * - if $config['slugField'] is an array of more than one item:
	 * 	 A list of database columns to base the slugs on, corresponding in length
	 * 	 Note that slugs based on multiple fields are not possible in this configuration.
	 * @param Array $config Configuration values
	 * @return Void
	 */
	protected function _setup($config) {
		if (!array_key_exists('baseField', $config)) {
			throw new Garp_Model_Behavior_Exception(sprintf(self::EXCEPTION_MISSING_CONFIG, 'baseField'));
		}
		$this->_normalizeBaseFieldConfiguration($config);
		$config['slugField'] = array_key_exists('slugField', $config) ? (array)$config['slugField'] : array('slug');

		$baseFieldCount = count($config['baseField']);
		$slugFieldCount = count($config['slugField']);

		// make sure array lengths match
		if ($slugFieldCount > 1 && $baseFieldCount !== $slugFieldCount) {
			throw new Garp_Model_Behavior_Exception(self::EXCEPTION_LENGTH_MISMATCH);
		}
		$this->_config = $config;
	}

	/**
	 * Before insert callback. Manipulate the new data here.
	 * @param Array $options The new data is in $args[1]
	 * @return Void
	 */
	public function beforeInsert(array &$args) {
		$model =  $args[0];
		$data  = &$args[1];

		$countBaseFields = count($this->_config['baseField']);
		$countSlugFields = count($this->_config['slugField']);

		// Scenario 1: one or more slugfields composed of a single basefield
		$method = '_addSlugFromSingle';
		if ($countBaseFields > 1 && $countSlugFields <= 1) {
			// Scenario 2: a single slug composed of multiple base fields
			$method = '_addSlugFromMultiple';
		}

		$referenceData = $data;

		// If the baseFields are not found in $data, and the model is multilingual,
		// we might be able to cllect the baseField(s) from its primary language counterpart
		$hasLangColumn = !empty($data[Garp_Model_Behavior_Translatable::LANG_COLUMN]);
		if ($model->isMultilingual() && $hasLangColumn) {
			$this->_modifyReferenceDataForMultilingualModels($referenceData, $model);
		}

		$this->{$method}($model, $data, $referenceData);
	}

	/**
 	 * For multilingual models: combine the referenceData with fresh data
 	 * from its counterpart in the primary language.
 	 * @param Array $referenceData
 	 * @param Garp_Model_Db $model
 	 * @return Void
 	 */
	protected function _modifyReferenceDataForMultilingualModels(array &$referenceData, Garp_Model_Db $model) {
		// Try to fetch the baseFields from the localised model
		$i18nModelFactory = new Garp_I18n_ModelFactory($referenceData[Garp_Model_Behavior_Translatable::LANG_COLUMN]);
		$unilingualModel = $model->getUnilingualModel();
		$localizedModel = $i18nModelFactory->getModel($unilingualModel);
		$localizedModel->unregisterObserver('Translatable');
		$localizedModel->unregisterObserver('Draftable');
		$referenceMap = $model->getReference(get_class($unilingualModel));

		// Construct a query that fetches the base fields from the parent model
		$baseFields = $this->_config['baseField'];
		$baseFields = $this->_baseFieldConfigToColumns($baseFields);
		$select = $localizedModel->select()
			->from($localizedModel->getName(), $baseFields)
		;
		foreach ($referenceMap['columns'] as $i => $col) {
			if (!isset($referenceMap['refColumns'][$i])) {
				throw new Exception('ReferenceMap is invalid: columns does not match up with refColumns.');
			}
			$refCol = $referenceMap['refColumns'][$i];
			// If the required foreign key is not in $referenceData, we won't be able to solve the problem
			if (!isset($referenceData[$col])) {
				return null;
			}
			$select->where("$refCol = ?", $referenceData[$col]);
		}
		$localizedRecord = $localizedModel->fetchRow($select)->toArray();
		$referenceData = array_merge($localizedRecord, $referenceData);
	}

	/**
 	 * Convert baseField configuration to a list of columns.
 	 * @param Array $baseFields
 	 * @return Array
 	 */
	protected function _baseFieldConfigToColumns(array $baseFields) {
		$out = array();
		foreach ($baseFields as $baseField) {
			$out[] = $baseField['column'];
		}
		return $out;
	}

	/**
 	 * Add single slug from multiple sources
 	 * @param Garp_Model_Db $model
 	 * @param Array $data Data that will be inserted: should receive the slug
 	 * @param Array $referenceData Data to base the slug on
 	 * @return Void
 	 */
	protected function _addSlugFromMultiple(Garp_Model_Db $model, array &$targetData, array $referenceData) {
		$baseFields = $this->_config['baseField'];
		$slugField  = $this->_config['slugField'][0];
		$baseData = array();
		foreach ((array)$baseFields as $baseColumn) {
			$baseData[] = $this->_getBaseString($baseColumn, $referenceData);
		}
		$baseData = implode(' ', $baseData);
		$targetData[$slugField] = $this->generateUniqueSlug($baseData, $model, $slugField);
	}		

	/**
 	 * Add slug(s) from single source
 	 * @param Garp_Model_Db $model
 	 * @param Array $data Data that will be inserted: should receive the slug
 	 * @param Array $referenceData Data to base the slug on
 	 * @return Void
 	 */
	protected function _addSlugFromSingle(Garp_Model_Db $model, array &$targetData, array $referenceData) {
		$baseFields = $this->_config['baseField'];
		$slugFields = $this->_config['slugField'];
		foreach ($baseFields as $i => $baseField) {
			$baseData = $this->_getBaseString($baseField, $referenceData);
			$slugField = $slugFields[$i];
			$targetData[$slugField] = $this->generateUniqueSlug($baseData, $model, $slugField);
		}
	}
	
	/**
 	 * Construct base string on which the slug will be based.
 	 * @param Array $baseFields Basefield configuration (per field)
 	 * @param Array $data Submitted data
 	 * @return String
 	 */
	protected function _getBaseString($baseField, $data) {
		$type = $baseField['type'];
		$method = '_getBaseStringFrom' . ucfirst($type);
		return $this->{$method}($data, $baseField);
	}

	/**
 	 * Get base string from text column.
 	 * @param Array $data 
 	 * @param Array $baseField Base field config
 	 * @return String
 	 */
	protected function _getBaseStringFromText(array $data, array $baseField) {
		$col = $baseField['column'];
		if (!array_key_exists($col, $data)) {
			return '';
		}
		return $data[$col];
	}

	/**
 	 * Get base string from date column.
 	 * @param Array $data
 	 * @param Array $baseField Base field config
 	 * @return String
 	 */
	protected function _getBaseStringFromDate(array $data, array $baseField) {
		$col = $baseField['column'];
		$format = $baseField['format'] ?: 'd-m-Y';
		if (!array_key_exists($col, $data)) {
			return '';
		}
		return date($format, strtotime($data[$col]));
	}
	
	/**
	 * Generate a slug from a base string
	 * @param String $base String to base the slug on.
	 * @return String $slug The generated slug
	 */
	public function generateSlug($base) {
		return Garp_Util_String::toDashed(strtolower($base));
	}

	/**
	* Generate a slug from a base string that is unique in the database
	* @param String $base
	* @param Model $model Model object of this record
	* @param String $slugField Name of the slug column in the database
	* @return String $slug The generated unique slug
	*/
	public function generateUniqueSlug($base, $model, $slugField) {
		$slug = $this->generateSlug($base);
		$select = $model->getAdapter()->select()
			->from($model->getName(), 'COUNT(*)')
		;
		$this->_setWhereClause($select, $slugField, $slug, $model);
		$n = 1;
		while ($this->_rowsExist($select)) {
			$this->_incrementSlug($slug, ++$n);
			$this->_setWhereClause($select, $slugField, $slug, $model);
		}
		return $slug;
	}

	/**
 	 * Set WHERE clause that checks for slug existence.
 	 * @param Zend_Db_Select $select
 	 * @param String $slugField
 	 * @param String $slug
 	 * @param Garp_Model_Db $model
 	 * @return Void
 	 */
	protected function _setWhereClause(Zend_Db_Select &$select, $slugField, $slug, Garp_Model_Db $model) {
		$slugField = $model->getAdapter()->quoteIdentifier($slugField);
		$select->reset(Zend_Db_Select::WHERE)
			->where($slugField . ' = ?', $slug);
	}		

	/**
	 * Check if rows exist for a given select query
	 * @param Zend_Db_Select $select 
	 * @return Boolean
	 */
	protected function _rowsExist(Zend_Db_Select $select) {
		$result = $select->query()->fetchAll();
		return !!$result[0]['COUNT(*)'];
	}

	/**
	 * Increment the version number in a slug
	 * @param String $slug
	 * @param Int $n Version number
	 * @return String The new slug
	 */
	protected function _incrementSlug(&$slug, $n) {
		$slug = preg_match('/'.static::VERSION_SEPARATOR.'[0-9]+$/', $slug) ? 
				preg_replace('/('.static::VERSION_SEPARATOR.'[0-9]+$)/', static::VERSION_SEPARATOR.$n, $slug) : 
				$slug.static::VERSION_SEPARATOR.$n;
	}

	/**
 	 * Normalize baseField configuration
 	 * @param Array $config
 	 * @return Void
 	 */
	protected function _normalizeBaseFieldConfiguration(&$config) {
		$bf_config = (array)$config['baseField'];
		$nbf_config = array();
		$defaults = array(
			'type' => 'text',
			'format' => null
		);
		foreach ($bf_config as $bf) {
			$bf = is_string($bf) ? array('column' => $bf) : $bf;
			$bf = array_merge($defaults, $bf);
			$nbf_config[] = $bf;
		}
		$config['baseField'] = $nbf_config;
	}
}
