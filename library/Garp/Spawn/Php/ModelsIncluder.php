<?php
require_once APPLICATION_PATH . '/../library/Zend/Config/Ini.php';
require_once APPLICATION_PATH . '/../library/Garp/Config/Ini.php';

/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Php_ModelsIncluder {
	const _MODELS_INCLUDE_FILE = '/configs/content.ini';
	const _ACL_FILE = '/configs/acl.ini';
	
	/**
	 * @var String $_modelId
	 */
	protected $_modelId;


	public function __construct($modelId) {
		$this->_modelId = $modelId;

		$this->_addToAclIni();
		$this->_addToContentIni();
	}
	
	/**
	 * @return String
	 */
	public function getModelId() {
		return $this->_modelId;
	}
	
	/**
	 * @param String $modelId
	 */
	public function setModelId($modelId) {
		$this->_modelId = $modelId;
		return $this;
	}

	protected function _addToAclIni() {
		$ini = $this->_getAclIni();

		if ($this->_modelIsIncludedInAclIni($ini)) {
			return;
		}

		$newLines =
			  "\n\nacl.resources.Model_{$this->_modelId}.id = Model_{$this->_modelId}\n"
			. "acl.resources.Model_{$this->_modelId}.allow.all.roles = \"admin\""
		;
		$newContent = $this->_addToIni($ini, $newLines);

		if (!file_put_contents($this->_getAclIniPath(), $newContent)) {
			throw new Exception("Could not append the '{$this->_modelId}' include to " . self::_ACL_FILE);
		}
	}

	protected function _addToContentIni() {
		$ini = $this->_getContentIni();
				
		if ($this->_modelIsIncludedInContentIni($ini)) {
			return;
		}

		$newLines = "\ncontent.commands.{$this->_modelId}.class = \"Model_{$this->_modelId}\"";
		$newContent = $this->_addToIni($ini, $newLines);

		if (!file_put_contents($this->_getContentIniPath(), $newContent)) {
			throw new Exception("Could not append the '{$this->_modelId}' include to " . self::_MODELS_INCLUDE_FILE);
		}
	}


	/**
	 * @param String $content The full content of the ini file
	 * @param String $env The targeted environment section
	 * @return Array The lines from the ini file's [production] section
	 */
	protected function _fetchLinesFromIniEnv($content, $env = 'production') {
		$contentEnvSections = $this->_splitIniIntoEnvSections($content);

		//	find production environment section in configuration
		foreach ($contentEnvSections as $envIndex => $envSection) {
			if (strpos($envSection, '[' . $env . ']') !== false) {
				$productionEnvContent = &$contentEnvSections[$envIndex + 1];
				break;
			}
		}
		if (isset($productionEnvContent)) {
			return explode("\n", $productionEnvContent);
		}
	}
	
	
	/**
	 * @param String $content The full content of the ini file
	 * @param String $newContent The new content to be added to the targeted environment section
	 * @param String $env The targeted environment section
	 * @return String The full content of the ini file, including the new lines and all the environment sections
	 */
	protected function _addToIni($content, $newContent, $env = 'production') {
		$contentEnvSections = $this->_splitIniIntoEnvSections($content);

		//	find production environment section in configuration
		foreach ($contentEnvSections as $envIndex => $envSection) {
			if (strpos($envSection, '[' . $env . ']') !== false) {
				$productionEnvContent = &$contentEnvSections[$envIndex + 1];
				$productionEnvContent = rtrim($productionEnvContent) . $newContent . "\n\n\n";
				break;
			}
		}

		return implode("", $contentEnvSections);
	}
	

	/**
	 * @param String $content The full content of the ini file
	 * @return Array The content, split up in section labels and their content
	 */
	protected function _splitIniIntoEnvSections($content) {
		return preg_split('/(\[[\w\s:]+\])/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	}
	
	/**
	 * @param 	String	$ini
	 */
	protected function _modelIsIncludedInContentIni($ini) {
		$path 		= $this->_getContentIniPath();
		$ini = new Garp_Config_Ini($path, 'production');
		$modelId = $this->getModelId();
		
		return isset($ini->content->commands->{$modelId}->class);
	}
	
	/**
	 * @param 	String	$ini
	 */
	protected function _modelIsIncludedInAclIni($ini) {
		$path 		= $this->_getAclIniPath();
		$ini 		= new Garp_Config_Ini($path, 'production');
		$modelId 	= $this->getModelId();
		$modelClass = 'Model_' . $modelId;

		return isset($ini->acl->resources->{$modelClass}->id);
	}

	protected function _getContentIni() {
		return file_get_contents($this->_getContentIniPath());
	}
	
	
	protected function _getAclIni() {
		return file_get_contents($this->_getAclIniPath());
	}
	
	
	protected function _getContentIniPath() {
		return APPLICATION_PATH . self::_MODELS_INCLUDE_FILE;
	}
	
	
	protected function _getAclIniPath() {
		return APPLICATION_PATH . self::_ACL_FILE;
	}
}