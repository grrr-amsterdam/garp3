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
		$this->_normalizeModel($model);
		$langSuffix = ucfirst(strtolower($this->_language));
		// Sanity check: is the model already localized?
		if ($this->_modelIsLocalized($model)) {
			throw new Garp_I18n_ModelFactory_Exception_ModelAlreadyLocalized(
				"Looks like model $model is already internationalized."
			);
		}
		$modelName = $model.$langSuffix;
		$model = new $modelName();
		return $model;
	}

	/**
 	 * Retrieve an internationalized bindingModel. Its referenceMap
 	 * will be tweaked to reflect the changes given as the second parameter.
 	 * @param Garp_Model_Db|String $model The original model, based on a table.
 	 * @return Garp_Model_Db
 	 */
	public function getBindingModel($bindingModel) {
		$this->_normalizeModel($bindingModel);
		$bindingModel = new $bindingModel();

		$referenceMap = $bindingModel->getReferenceMapNormalized();
		foreach ($referenceMap as $rule => $reference) {
			// Check here wether we need to internationalize the refTableClass
			$refModel = new $reference['refTableClass'];
			if ($refModel->getObserver('Translatable')) {
				try {
					$refModel = $this->getModel($reference['refTableClass']);
				} catch (Garp_I18n_ModelFactory_Exception_ModelAlreadyLocalized $e) {
					continue;
				}
			}
			$refTableClass = get_class($refModel);
			$bindingModel->addReference(
				$rule,
				$reference['columns'],
				$refTableClass,
				$reference['refColumns']
			);
		}
		return $bindingModel;
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

	/**
 	 * Go from a modelname to a model object
 	 * @param Mixed $model
 	 * @return Garp_Model_Db
 	 */
	protected function _normalizeModel(&$model) {
		if ($model instanceof Garp_Model_Db) {
			$model = get_class($model);
		}
		$model = strpos($model, 'Model_') !== false ? $model : 'Model_' . $model;
	}

	protected function _modelIsLocalized($model) {
		$languages = Garp_I18n::getLocales();
		foreach ($languages as $lang) {
			$langSuffix = ucfirst($lang);
			if (preg_match("/[a-z0-9]+$langSuffix$/", $model)) {
				return true;
			}
		}
		return false;
	}
}
