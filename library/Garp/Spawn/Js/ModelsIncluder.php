<?php
/**
 * Garp_Spawn_Js_ModelsIncluder
 * Writes script tags linking JS models to the CMS models include file.
 *
 * @package Garp_Spawn
 * @author  David Spreekmeester <david@grrr.nl>
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Spawn_Js_ModelsIncluder {
    const MODELS_INCLUDE_FILE_DEFAULT = '/modules/default/views/scripts/partials/models.phtml';
    const START_TAG = 'BEGIN SPAWNED CALLS';
    const END_TAG = 'END SPAWNED CALLS';
    const JS_GARP_PATH = '/js/garp/models/';
    const JS_APP_PATH = '/js/models/';
    const SCRIPT_TAG = '<script crossorigin="anonymous" src="%s"></script>';
    const EXCEPTION_WRITE_ERROR = 'Could not save the model includes file.';

    public function __construct(Garp_Spawn_Model_Set $modelSet) {
        $modelsIncludeFile = explode("\n", file_get_contents($this->_getIncludesFilename()));

        foreach ($modelsIncludeFile as $i => $line) {
            if (strpos($line, self::START_TAG) !== false) {
                $startLine = $i;
            } elseif (strpos($line, self::END_TAG) !== false) {
                $endLine = $i;
            }
        }

        $fileHead = implode("\n", array_slice($modelsIncludeFile, 0, $startLine + 1));
        $fileFoot = implode("\n", array_slice($modelsIncludeFile, $endLine));
        $spawnerCalls = '';

        foreach ($modelSet as $model) {
            if ($model->module === 'garp') {
                $spawnerCalls .= $this->_createScriptInclude($model->id, self::JS_GARP_PATH) . "\n";
            }

            $spawnerCalls .= $this->_createScriptInclude($model->id, self::JS_APP_PATH) . "\n";
        }

        return $this->_save(implode("\n", array($fileHead, $spawnerCalls, $fileFoot)));
    }

    protected function _createScriptInclude($modelId, $path) {
        return sprintf(self::SCRIPT_TAG, $this->_getAssetUrlCall($path, $modelId));
    }

    protected function _getAssetUrlCall($path, $modelId) {
        $config = Zend_Registry::get('config');

        // Check to see if we're using twig templates
        if (isset($config->resources->view->engines->twig->isDefault)
            && $config->resources->view->engines->twig->isDefault
        ) {
            return '{{ zf.assetUrl(\'' . $path . $modelId . '.js\') }}';
        }

        return '<?php echo $this->assetUrl(\'' . $path . $modelId . '.js\') ?>';
    }

    protected function _getIncludesFilename() {
        $ini = Zend_Registry::get('config');
        if (isset($ini->spawn->js->modelLoaderFile)) {
            return APPLICATION_PATH . $ini->spawn->js->modelLoaderFile;
        }

        return APPLICATION_PATH . self::MODELS_INCLUDE_FILE_DEFAULT;
    }

    /**
     * Write the file
     *
     * @param String $content The full content to write to the includes file.
     * @return bool
     */
    protected function _save($content) {
        if (!file_put_contents($this->_getIncludesFilename(), $content)) {
            throw new Exception(sprintf(self::EXCEPTION_WRITE_ERROR));
        }
        return true;
    }
}
