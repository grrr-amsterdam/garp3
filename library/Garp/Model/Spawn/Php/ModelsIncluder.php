<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Php_ModelsIncluder {
	const _MODELS_INCLUDE_FILE = '/configs/content.ini';


	public function __construct($modelId) {
		$modelIsIncluded = false;
		$modelIncludes = file_get_contents($this->_getIncludesFilename());

		$modelIncludesSections = preg_split('/(\[[\w\s:]+\])/', $modelIncludes, -1, PREG_SPLIT_DELIM_CAPTURE);

		//	find production environment section in configuration
		foreach ($modelIncludesSections as $sectionIndex => $section) {
			if (strpos($section, '[production]') !== false) {
				$productionEnvSection = &$modelIncludesSections[$sectionIndex + 1];
				break;
			}
		}
		if (isset($productionEnvSection)) {
			$lines = explode("\n", $productionEnvSection);
			foreach ($lines as $line) {
				$matches = array();
				preg_match('/content.commands.(?P<model>\w+).class = "(G_)?Model_\w+?"/i', trim($line), $matches);
				if (
					array_key_exists('model', $matches) &&
					strcasecmp($matches['model'], $modelId) == 0
				) {
					$modelIsIncluded = true;
					break;
				}
			}

			if ($modelIsIncluded) {
				p("  PHP model is already included in admin includes.");
			} else {
				$productionEnvSection = "\n".trim($productionEnvSection);
				$productionEnvSection.= "\ncontent.commands.{$modelId}.class = \"Model_{$modelId}\"\n\n\n";
				$modelIncludes = implode("", $modelIncludesSections);

				if (file_put_contents($this->_getIncludesFilename(), $modelIncludes)) {
					p("√ Added PHP model to the admin includes.");
				} else throw new Exception("Could not append the admin include for the '{$modelId}' PHP model.");
			}
		} else throw new Exception("Could not find the configuration for the 'production' environment in ".self::_MODELS_INCLUDE_FILE);
	}


	protected function _getIncludesFilename() {
		return APPLICATION_PATH.self::_MODELS_INCLUDE_FILE;
	}
}