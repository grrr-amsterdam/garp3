<?php
/**
 * Garp_Api
 * Helper functions concerning the API.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Api {
	/**
	 * An object laying out the API. Used by {@link Garp_ExtController}
	 * @var stdClass
	 */
	protected $_layout = null;
	
	
	/**
	 * The namespace for all models.
	 * @var String
	 */
	const ENTITIES_NAMESPACE = 'Model';

	
	/**
	 * Return the API layout, e.g. which methods may be called on which entities.
	 * @return stdClass
	 */
	public function getLayout() {
		$methods = array(
		//  Method            These are the rights in ACL that allow for the method to be executed
			'fetch'        => array('fetch', 'fetch_own'),
			'create',
			'update'       => array('update', 'update_own'),
			'destroy'      => array('destroy', 'destroy_own'),
			'count'        => array('fetch'),
			'relate'
		);
		$auth = Garp_Auth::getInstance();

		if (is_null($this->_layout)) {
			// read content managing configuration from content.ini
			// note; Garp_Cache_Config is not used here because we always want fresh data in the CMS, 
			// no cached versions
			$config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/content.ini', APPLICATION_ENV);
			$classes = $config->content->commands;
			$api = new stdClass();
			$api->actions = array();
			foreach ($classes as $key => $class) {
				$alias = !empty($class->alias) ? $class->alias : $key;
				$modelName = self::modelAliasToClass($alias);

				if (!array_key_exists($alias, $api->actions)) {
					$api->actions[$alias] = array();
				}
				
				foreach ($methods as $method => $privileges) {
					if (is_numeric($method)) {
						$method = $privileges;
						$privileges = array($method);
					}
					// Check if any of the given privileges allow for the method to be executed
					$allowed = false;
					foreach ($privileges as $privilege) {
						if ($auth->isAllowed($modelName, $privilege)) {
							$allowed = true;
							break;
						}
					}
					// If the method is not allowed, don't mention it in the SMD
					if (!$allowed) {
						continue;
					}

					$api->actions[$alias][] = array(
						'name'	=> $method,
						'len'	=> 1 // always expect 1 argument: an array containing named arguments
					);
				}
			}
			$this->_layout = $api;
		}
		return $this->_layout;
	}
	
	
	/**
	 * Retrieve a list of all models
	 * @return Array
	 */
	public static function getAllModels() {
		// note; Garp_Cache_Config is not used here because we always want fresh data in the CMS, 
		// no cached versions
		$config = new Zend_Config_Ini(APPLICATION_PATH.'/configs/content.ini', APPLICATION_ENV);
		$classes = $config->content->commands;
		return $classes;
	}


	/**
	 * Convert a model alias to the real class name
	 * @param String $model
	 * @return String
	 */
	public static function modelAliasToClass($model) {
		$classes = self::getAllModels();

		foreach ($classes as $class) {
			if ($class->alias === $model || $class->class === self::ENTITIES_NAMESPACE.'_'.$model) {
				return $class->class;
			}
		}
		throw new Garp_Content_Exception('Invalid model alias specified: '.$model);
	}
}
