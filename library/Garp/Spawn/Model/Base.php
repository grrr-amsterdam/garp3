<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Model_Base extends Garp_Spawn_Model_Abstract {

	/**
	 * @var Garp_Spawn_Model_I18n $_i18nModel
	 */
	protected $_i18nModel;	
	


	public function __construct(Garp_Spawn_Config_Model_Abstract $config) {
		parent::__construct($config);

		if ($this->isMultilingual()) {
			$i18nModelConfig 	= new Garp_Spawn_Config_Model_I18n($config);
			$i18nModel 			= new Garp_Spawn_Model_I18n($i18nModelConfig);
			$this->setI18nModel($i18nModel);
		}
	}
	
	/**
	 * @return Garp_Spawn_Model_I18n
	 */
	public function getI18nModel() {
		return $this->_i18nModel;
	}
	
	/**
	 * @param Garp_Spawn_Model_I18n $i18nModel
	 */
	public function setI18nModel($i18nModel) {
		$this->_i18nModel = $i18nModel;
	}
	
	public function materializePhpModels(Garp_Spawn_Model_Abstract $model) {
		parent::materializePhpModels($model);

		if ($this->isMultilingual()) {
			$i18nModel = $this->getI18nModel();
			parent::materializePhpModels($i18nModel);
		}
	}

	/**
	 * Creates extended model files, if necessary.
	 */
	public function materializeExtendedJsModels(Garp_Spawn_Model_Set $modelSet) {
		$jsExtendedModel = new Garp_Spawn_Js_Model_Extended($this->id, $modelSet);
		$jsExtendedModelOutput = $jsExtendedModel->render();

		$jsAppExtendedFile = new Garp_Spawn_Js_Model_File_AppExtended($this);
		$jsAppExtendedFile->save($jsExtendedModelOutput);

		if ($this->module === 'garp') {
			$jsGarpExtendedFile = new Garp_Spawn_Js_Model_File_GarpExtended($this);
			$jsGarpExtendedFile->save($jsExtendedModelOutput);
		}
	}

	/**
	 * Creates JS base model file.
	 */
	public function renderJsBaseModel(Garp_Spawn_Model_Set $modelSet) {
		$jsBaseModel = new Garp_Spawn_Js_Model_Base($this->id, $modelSet);
		$jsBaseFile = new Garp_Spawn_Js_Model_File_Base($this);
		return $jsBaseModel->render();
	}

	/**
	 * @return 	Bool 	Whether this is a base model containing one or more multilingual columns
	 */
	public function isMultilingual() {
		$fields = $this->fields->getFields('multilingual', true);
		$isMultilingual = (bool)$fields;

		return $isMultilingual;
	}
}