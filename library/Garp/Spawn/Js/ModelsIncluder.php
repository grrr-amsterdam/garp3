<?php
/**
 * @author David Spreekmeester | grrr.nl
 */
class Garp_Spawn_Js_ModelsIncluder {
	const _MODELS_INCLUDE_FILE_DEFAULT = '/modules/default/views/scripts/partials/models.phtml';
	const _START_TAG = 'BEGIN SPAWNED CALLS';
	const _END_TAG = 'END SPAWNED CALLS';
	const _JS_GARP_PATH = '/js/garp/models/';
	const _JS_APP_PATH = '/js/models/';

	
	public function __construct(Garp_Spawn_Model_Set $modelSet) {
		$modelsIncludeFile = explode("\n", file_get_contents($this->_getIncludesFilename()));

		foreach ($modelsIncludeFile as $i => $line) {
			if (strpos($line, self::_START_TAG) !== false) {
				$startLine = $i;
			} elseif (strpos($line, self::_END_TAG) !== false) {
				$endLine = $i;
			}
		}
		
		$fileHead = implode("\n", array_slice($modelsIncludeFile, 0, $startLine + 1));
		$fileFoot = implode("\n", array_slice($modelsIncludeFile, $endLine));
		$spawnerCalls = '';

		foreach ($modelSet as $model) {
			if ($model->module === 'garp') {
				$this->_appendToIncludes($model->id, self::_JS_GARP_PATH, $spawnerCalls);
			}

			$this->_appendToIncludes($model->id, self::_JS_APP_PATH, $spawnerCalls);
		}
		
		if (!$this->_save(
			$fileHead . "\n"
			. $spawnerCalls . "\n"
			. $fileFoot
		)) {
			throw new Exception("Could not save the model includes file.");
		}
	}


	protected function _appendToIncludes($modelId, $path, &$output) {
		$newEntry = '<script src="<?php echo $this->baseUrl(\''.$path.$modelId.'.js\') ?>"></script>';
		$output .= $newEntry;
	}


	protected function _getIncludesFilename() {
		$ini = Zend_Registry::get('config');
		if (@$ini->spawn->js->modelLoaderFile) {
			return APPLICATION_PATH . $ini->spawn->js->modelLoaderFile;
		}
		
		return APPLICATION_PATH . self::_MODELS_INCLUDE_FILE_DEFAULT;
	}
	

	/**
	 * @param String $content The full content to write to the includes file.
	 */
	protected function _save($content) {
		if (!file_put_contents($this->_getIncludesFilename(), $content)) {
			throw new Exception ("Could not append the admin frontend include for the '{$modelId}' Javascript model.");
		} else return true;
	}
}