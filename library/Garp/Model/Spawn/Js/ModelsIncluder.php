<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Model_Spawn_Js_ModelsIncluder {
	const _MODELS_INCLUDE_FILE = '/modules/default/views/scripts/partials/models.phtml';

	
	public function __construct(Garp_Model_Spawn_Model $model) {
		if ($this->_isModelIncluded($model->id)) {
			p("  Javascript model is already included in admin frontend.");
		} else {
			if ($this->_appendToIncludes($model)) {
				p("√ Added Javascript model to the admin frontend includes.");
			} else throw new Exception("Could not append the admin frontend include for the '{$model->id}' Javascript model.");
		}
	}
	
	
	protected function _isModelIncluded($modelId) {
		$modelsIncludes = file_get_contents($this->_getIncludesFilename());
		$lines = explode("\n", $modelsIncludes);
		$baseModelIsIncluded = false;
		$extendedModelIsIncluded = false;

		foreach ($lines as $line) {
			$matches = array();
			preg_match('/<script src="<\?php echo \$this->baseUrl\(\'\/js\/(garp\/models|models\/base)+\/(?P<model>\w+).js\'\) \?>"><\/script\>/i', trim($line), $matches);
			if (
				array_key_exists('model', $matches) &&
				strcasecmp($matches['model'], $modelId) == 0
			) {
				$baseModelIsIncluded = true;
			} else {
				$matches = array();
				preg_match('/<script src="<\?php echo \$this->baseUrl\(\'\/js\/models\/(?P<model>\w+).js\'\) \?>"><\/script\>/i', trim($line), $matches);
				if (
					array_key_exists('model', $matches) &&
					strcasecmp($matches['model'], $modelId) == 0
				) {
					$extendedModelIsIncluded = true;
				}
			}
		}

		return $baseModelIsIncluded && $extendedModelIsIncluded;
	}
	
	
	protected function _appendToIncludes(Garp_Model_Spawn_Model $model) {
		$newEntry = '<script src="<?php echo $this->baseUrl(\'/js/'
			.($model->module === 'garp' ? 
				'garp/models/' :
				'models/base/'
			)
			.$model->id.'.js\') ?>"></script>'
		;
		$newEntry.= "\n"
					.'<script src="<?php echo $this->baseUrl(\'/js/models/'.$model->id.'.js\') ?>"></script>';
		$writeMode = !filesize($this->_getIncludesFilename()) ? 0 : FILE_APPEND;
		return (bool)file_put_contents($this->_getIncludesFilename(), "\n".$newEntry, $writeMode);
	}
	
	protected function _getIncludesFilename() {
		return APPLICATION_PATH.self::_MODELS_INCLUDE_FILE;
	}
}