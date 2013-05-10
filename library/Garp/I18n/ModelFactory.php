<?php
/**
 * Garp_I18n_ModelFactory
 * Will return a model based on an internationalized view.
 * This requires the model to be spawned with certain i18n properties.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_I18n
 */
class Garp_I18n_ModelFactory {
	/**
 	 * @var String
 	 */
	protected $_language;

	/**
 	 * Class constructor
 	 * @param String $language
 	 * @return Void
 	 */
	public function __construct($language = null) {
		// If no language is given, try to read from the Registry
		if (!$language) {
			$language = Garp_I18n::getCurrentLocale();
		}
		if (!$language) {
			throw new Garp_I18n_Exception('No language found! Make sure at least one language is available.');
		}
		$this->setLanguage($language);
	}

	/**
 	 * Load the model
 	 * @param Garp_Model_Db|String $model The original model, based on a table.
 	 * @return Garp_Model_Db
 	 */
	public function getModel($model) {
		if (is_string($model)) {
			$model = (substr($model, 0, 6) !== 'Model_' ? 'Model_' : '') . $model;
			$model = new $model;
		} else {
			$model = clone $model;
		}
		$viewName = $model->getName() . '_' . $this->_language;
		$model->setOptions(array(
			Zend_Db_Table_Abstract::NAME => $viewName
		));
		return $model;
	}

	/**
 	 * Set language
 	 * @param String $language
 	 * @return Garp_I18n_ModelFactory $this, for a fluent interface
	 */
	public function setLanguage($language) {
		$this->_language = $language;
		return $this;
	}

	/**
 	 * Get language
 	 * @return String
 	 */
	public function getLanguage() {
		return $this->_language;
	}
}
