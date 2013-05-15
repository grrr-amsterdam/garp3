<?php
/**
 * Garp_Model_ReferenceMapLocalizer
 * Adds localized models to a referenceMap
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      1.0
 * @package      Garp_Model
 */
class Garp_Model_ReferenceMapLocalizer {
	/**
 	 * Subject model
 	 * @var Garp_Model_Db
 	 */
	protected $_model;

	/**
	 * Class constructor
	 * @param Garp_Model_Db $model Subject model
	 * @return Void
	 */
	public function __construct(Garp_Model_Db $model) {
		$this->setModel($model);
	}

	/**
 	 * Populate the subject model's referenceMap with
 	 * localized versions of the given model.
 	 * @param String|Garp_Model_Db $relatedModel 
 	 * @return Void
 	 */
	public function populate($relatedModel) {
		// Sanity check: does the model have a reference to the 
		// given model in the first place?
		// This will throw an exception if not.
		$relatedModel = (substr($relatedModel, 0, 6) !== 'Model_' ? 'Model_' : '') . $relatedModel;
		$ref = $this->_model->getReference($relatedModel);
		$locales = Garp_I18n::getLocales();
		foreach ($locales as $locale) {
			$factory = new Garp_I18n_ModelFactory($locale);
			$localizedModel = $factory->getModel($relatedModel);
			$localizedModelName = get_class($localizedModel);
			$cleanLocalizedName = $localizedModel->getNameWithoutNamespace();
			$this->_model->addReference(
				$cleanLocalizedName,
				$ref[Zend_Db_Table_Abstract::COLUMNS],
				$localizedModelName,
				$ref[Zend_Db_Table_Abstract::REF_COLUMNS]
			);
		}
	}

	/**
	 * Get model
	 * @return Garp_Model_Db
	 */
	public function getModel() {
		return $this->_model;
	}

	/**
	 * Set model
	 * @param Garp_Model_Db model
	 * @param Mixed $value
	 * @return Void
	 */
	public function setModel($model) {
		$this->_model = $model;
	}
}
