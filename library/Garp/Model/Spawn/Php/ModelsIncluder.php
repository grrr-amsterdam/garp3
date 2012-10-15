<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Php_ModelsIncluder {
	const _MODELS_INCLUDE_FILE = '/configs/content.ini';
	const _ACL_FILE = '/configs/acl.ini';
	
	protected $_modelId;


	public function __construct($modelId) {
		$this->_modelId = $modelId;

		$this->_addToAclIni();
		$this->_addToContentIni();
	}


	protected function _addToAclIni() {
		$ini = $this->_getAclIni();
		$modelIsIncluded = false;

		$prodEnvContentLines = $this->_fetchLinesFromIniEnv($ini);

		if ($prodEnvContentLines) {
			foreach ($prodEnvContentLines as $line) {
				if ($this->_modelIsIncludedInAclIniLine($line)) {
					$modelIsIncluded = true;
					break;
				}
			}

			if (!$modelIsIncluded) {
				$newLines =
					  "\n\nacl.resources.Model_{$this->_modelId}.id = Model_{$this->_modelId}\n"
					. "acl.resources.Model_{$this->_modelId}.allow.all.roles = \"admin\""
				;
				$newContent = $this->_addToIni($ini, $newLines);

				if (!file_put_contents($this->_getAclIniPath(), $newContent)) {
					throw new Exception("Could not append the '{$this->_modelId}' include to " . self::_ACL_FILE);
				}
			}
		} else throw new Exception("Could not find the configuration for the 'production' environment in " . self::_ACL_FILE);
	}


	protected function _addToContentIni() {
		$ini = $this->_getContentIni();
		$modelIsIncluded = false;

		$prodEnvContentLines = $this->_fetchLinesFromIniEnv($ini);

		if ($prodEnvContentLines) {
			foreach ($prodEnvContentLines as $line) {
				if ($this->_modelIsIncludedInContentIniLine($line)) {
					$modelIsIncluded = true;
					break;
				}
			}

			if (!$modelIsIncluded) {
				$newLines = "\ncontent.commands.{$this->_modelId}.class = \"Model_{$this->_modelId}\"";
				$newContent = $this->_addToIni($ini, $newLines);

				if (!file_put_contents($this->_getContentIniPath(), $newContent)) {
					throw new Exception("Could not append the '{$this->_modelId}' include to " . self::_MODELS_INCLUDE_FILE);
				}
			}
		} else throw new Exception("Could not find the configuration for the 'production' environment in ".self::_MODELS_INCLUDE_FILE);
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
	
	
	protected function _modelIsIncludedInContentIniLine($line) {
		$matches = array();
		preg_match('/content.commands.(?P<model>\w+).class = "(G_)?Model_\w+?"/i', trim($line), $matches);
		return 
			array_key_exists('model', $matches) &&
			strcasecmp($matches['model'], $this->_modelId) == 0
		;
	}


	protected function _modelIsIncludedInAclIniLine($line) {
		$matches = array();
		preg_match('/acl.resources.Model_(?P<model>\w+).id = "?Model_\w+"?/i', trim($line), $matches);
		return 
			array_key_exists('model', $matches) &&
			$matches['model'] === $this->_modelId
		;
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