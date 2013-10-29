<?php
/**
 * Garp_Model_Behavior_Sluggable
 * Generates a unique slug for a record
 * @author Harmen Janssen, David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Behavior
 * @lastmodified $Date: $
 */
class Garp_Model_Behavior_Sluggable extends Garp_Model_Behavior_Abstract {
	/**
	 * Version separator (e.g. <base><version-separator><int>)
	 * @var String
	 */
	const VERSION_SEPARATOR = '-';
		
	
	/**
	 * Configuration
	 * @var Array
	 */
	protected $_config;
	
	
	/**
	 * Make sure the config array is at least filled with some default values to work with.
	 * @param Array $config Configuration values
	 * 					String or Array ['baseField']	String:
	 * 														The database column on which the slug should be based.
	 * 													Array:
	 * 														if $config['slugField'] is empty or a single value:
	 * 															A list of database columns that should be combined to base the slug on.
	 * 														if $config['slugField'] is an array of more than one item:
	 * 															A list of database columns to base the slugs on, corresponding in length
	 * 															Note that slugs combined of multiple fields are not possible in this configuration.
	 * @return Void
	 */
	protected function _setup($config) {
		if (!array_key_exists('baseField', $config)) {
			throw new Garp_Model_Behavior_Exception('"baseField" is a required config key');
		}
		$config['slugField'] = array_key_exists('slugField', $config) ? $config['slugField'] : 'slug';

		$baseFieldCount = count($config['baseField']);
		$slugFieldCount = count($config['slugField']);

		if ($slugFieldCount > 1) {
			/**
			 * There are multiple slug fields. 'slugField' must be an array as well
			 * with the same length.
			 */
			if ($baseFieldCount !== $slugFieldCount) {
				throw new Garp_Model_Behavior_Exception('"baseField" and "slugField" need to have the same '.
														'length when using multiple slugFields.');
			}			
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
		/**
		 * Multiple slug fields might be generated. That's why 
		 * we turn the values in arrays for easy processing.
		 */
		$baseFields = (array)$this->_config['baseField'];
		$slugFields = (array)$this->_config['slugField'];

		if (count($baseFields) > 1 && count($slugFields) <= 1) {
			/**
			 * This slug is composed of multiple basefields
			 */
			$baseData = '';
			foreach ($baseFields as $b) {
				if (!empty($data[$b])) {
					$baseData .= (!empty($baseData) ? ' ':'').$data[$b];
				}
			}
			$slugField = current($slugFields);
			$data[$slugField] = $this->generateUniqueSlug($baseData, $model, $slugField);
		} else {
			/**
			 * This record has one or more slugfields that are composed of a single basefield
			 */
			foreach ($baseFields as $i => $baseField) {
				$baseData = $this->_getBaseValue($model, $data, $baseField);
				if (!$baseData) {
					return;
				}
				$slugField = $slugFields[$i];
				$data[$slugField] = $this->generateUniqueSlug($baseData, $model, $slugField);
			}
		}
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
						->where($model->getAdapter()->quoteIdentifier($slugField).
								' = ?', $slug);
		$n = 1;
		while ($this->_rowsExist($select)) {
			$slug = $this->_incrementSlug($slug, ++$n);
			$select->reset(Zend_Db_Select::WHERE)
				   ->where($model->getAdapter()->quoteIdentifier($slugField).
							' = ?', $slug);
		}
		return $slug;
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
	protected function _incrementSlug($slug, $n) {
		return preg_match('/'.static::VERSION_SEPARATOR.'[0-9]+$/', $slug) ? 
				preg_replace('/('.static::VERSION_SEPARATOR.'[0-9]+$)/', static::VERSION_SEPARATOR.$n, $slug) : 
				$slug.static::VERSION_SEPARATOR.$n;
	}

	protected function _getBaseValue($model, $data, $baseField) {
		if (!empty($data[$baseField])) {
			return $data[$baseField];
		}
		$isMultilingual = $model->isMultilingual();
		$hasLangColumn = !empty($data[Garp_Model_Behavior_Translatable::LANG_COLUMN]);
		if (!$isMultilingual || !$hasLangColumn) {
			return null;
		}

		// Try to fetch the baseField from the localised model
		$i18nModelFactory = new Garp_I18n_ModelFactory($data[Garp_Model_Behavior_Translatable::LANG_COLUMN]);
		$unilingualModel = $model->getUnilingualModel();
		$localizedModel = $i18nModelFactory->getModel($unilingualModel);
		$localizedModel->unregisterObserver('Translatable');
		$localizedModel->unregisterObserver('Draftable');
		$referenceMap = $model->getReference(get_class($unilingualModel));

		// Construct a query that fetches the base fields from the parent model
		$select = $localizedModel->select()
			->from($localizedModel->getName(), array($baseField))
		;
		foreach ($referenceMap['columns'] as $i => $col) {
			if (!isset($referenceMap['refColumns'][$i])) {
				throw new Exception('ReferenceMap is invalid: columns does not match up with refColumns.');
			}
			$refCol = $referenceMap['refColumns'][$i];
			// If the required foreign key is not in $data, we won't be able to solve the problem
			if (!isset($data[$col])) {
				return null;
			}
			$select->where("$refCol = ?", $data[$col]);
		}
		$localizedRecord = $localizedModel->fetchRow($select);
		return $localizedRecord ? $localizedRecord->{$baseField} : null;
	}		
}
