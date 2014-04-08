<?php
/**
 * Garp_Model_Db_BindingManager
 * Manages bindings between models. A model's bindModel() and related methods
 * map to methods in this class.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Db_BindingManager {
	/**
	 * Max recursion with bindModel()
	 * @var Int
	 */
	const MAX_BINDING_RECURSION = 3;
	
	
	/**
	 * Stored bindings
	 * @var Array
	 */
	protected static $_bindings = array();
	
	
	/**
	 * Stored recursion levels.
	 * @var Array
	 */
	protected static $_recursion = array();
	
	
	/**
	 * Store a binding between models.
	 * @param String $subjectModel
	 * @param String $alias
	 * @param Garp_Util_Configuration $options
	 * @return Void
	 */
	public static function storeBinding($subjectModel, $alias, Garp_Util_Configuration $options = null) {		
		static::$_bindings[$subjectModel][$alias] = self::_setRelationDefaultOptions($alias, $options);
	}
	
	
	/**
	 * Remove a binding between models.
	 * @param String $subjectModel
	 * @param String $alias
	 * @return Void
	 */
	public static function removeBinding($subjectModel, $alias = false) {
		if ($alias) {
			unset(static::$_bindings[$subjectModel][$alias]);
		} else {
			static::$_bindings[$subjectModel] = array();
		}
		self::resetRecursion($subjectModel);
	}
	
	
	/**
	 * Get all bindings to a certain model
	 * @param String $subjectModel
	 * @return Array 
	 */
	public static function getBindings($subjectModel = null) {
		if (is_null($subjectModel)) {
			return static::$_bindings;
		}
		return !empty(static::$_bindings[$subjectModel]) ? static::$_bindings[$subjectModel] : array();
	}
	
	
	/**
	 * Register a fetch. The goal of this is to keep track of recursion.
	 * For instance; when Model_Post fetches Model_Comment, recursion = 1.
	 * Then Model_Comment fetches Model_Post again. 
	 * This triggers another fetch from Model_Post -> Model_Comment. Recursion is now 2.
	 * And so on and so forth until eternity. Or until self::MAX_BINDING_RECURSION is reached.
	 * Recursion is recorded as a string, namely "$subjectModel.$alias".
	 * @param String $subjectModel
	 * @param String $alias
	 * @return Void
	 */
	public static function registerFetch($subjectModel, $alias) {
		$key = self::getStoreKey($subjectModel, $alias);
		if (!array_key_exists($key, static::$_recursion)) {
			static::$_recursion[$key] = 1;
		} else {
			static::$_recursion[$key] += 1;
		}
	}
	
	
	/**
	 * Return recursion level for two models
	 * @param String $subjectModel
	 * @param String $alias
	 * @return Int
	 */
	public static function getRecursion($subjectModel, $alias) {
		$key = self::getStoreKey($subjectModel, $alias);
		return !array_key_exists($key, static::$_recursion) ? 0 : static::$_recursion[$key];
	}
	
	
	/**
	 * Reset recursion level for a binding to 0.
	 * If $alias is not given, all recursion associated with 
	 * $subjectModel will be reset.
	 * @param String $subjectModel
	 * @param String $alias
	 * @return Void
	 */
	public static function resetRecursion($subjectModel, $alias = false) {
		if ($alias) {
			$key = self::getStoreKey($subjectModel, $alias);
			unset(static::$_recursion[$key]);
		} else {
			$_store = array();
			foreach (static::$_recursion as $key => $value) {
				$keyBits = explode('.', $key);
				// Strip off the integer from the end in the case of homophyllic relationships.
				array_walk($keyBits, function(&$kb) {
					$kb = preg_replace('/\d$/', '', $kb);
				});

				if (!in_array($subjectModel, $keyBits)) {
					$_store[$key] = $value;
				}
			}
			static::$_recursion = $_store;
		}
	}
	
	
	/**
	 * Check if a subject model is still allowed to query the bound model.
	 * This is determined by the recursion level stored in self::$_recursion.
	 * @param String $subjectModel
	 * @param String $alias
	 * @return Boolean
	 */
	public static function isAllowedFetch($subjectModel, $alias) {
		return self::getRecursion($subjectModel, $alias) < self::MAX_BINDING_RECURSION;
	}
	
	
	/**
	 * Make sure relation options (passed to self::bindModel()) contain
	 * certain default values.
	 * @param String $alias The related model's alias
	 * @param Garp_Util_Configuration $options The given options
	 * @return Garp_Util_Configuration Modified options
	 */
	protected static function _setRelationDefaultOptions($alias, Garp_Util_Configuration $options = null) {
		$options = $options ?: new Garp_Util_Configuration();
		$options->setDefault('rule', null)
				->setDefault('rule2', null)
				->setDefault('conditions', null)
				->setDefault('mode', null)
				->setDefault('modelClass', $alias)
				;
		return $options;
	}
	
	
	/**
	 * Create a key by which a binding gets stored.
	 * @param String $subjectModel
	 * @param String $alias
	 * @return String
	 */
	public static function getStoreKey($subjectModel, $alias) {
		if ($subjectModel == $alias) {
			$subjectModel .= '1';
			$alias .= '2';
		}
		$models = array($subjectModel, $alias);
		sort($models);
		$key = implode('.', $models);
		return $key;
	}


	/**
 	 * Generate a binding tree of sorts.
 	 * This starts at the subject model and adds models while self::isAllowedFetch returns true.
 	 * At the end you have a tree that shows exactly the relationships between models.
 	 * @param String $model Root modelname
 	 * @param Int $level Stores how deep we are in the array, in order to avoid recursion limbo.
 	 * @return Array
 	 */
	public static function getBindingTree($rootModel, $level = 0) {
		if (!is_string($rootModel)) {
			throw new Exception(__METHOD__.' expects parameter 1 to be string.');
		}
		$tree = array();
		$bindingModels = static::getBindings($rootModel);
		foreach ($bindingModels as $boundModel => $bindingOptions) {
			$tree[$boundModel] = (array)$bindingOptions;

			if (static::getBindings($boundModel) && $level < static::MAX_BINDING_RECURSION) {
				$tree[$boundModel]['related'] = static::getBindingTree($boundModel, $level+1);
			}
		}
		return $tree;
	}
}
