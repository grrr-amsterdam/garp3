<?php
class Garp_Cli_Command_Models extends Garp_Cli_Command {

    /**
     * Garp models have moved from the G_Model namespace to the Garp_Model_Db_ namespace.
     * This command migrates extended models to the new namespace.
     */
    public function migrateGarpModels() {
        $modelDir = APPLICATION_PATH . '/modules/default/Model';
        $dirIterator = new DirectoryIterator($modelDir);
        foreach ($dirIterator as $finfo) {
            if ($finfo->isDot()) {
                continue;
            }
            $this->_modifyModelFile($finfo);
        }

        $this->_updateCropTemplateRefs();
    }

    // Change references to G_Model_CropTemplate
    protected function _updateCropTemplateRefs() {
        $iniPaths = ['/configs/content.ini', '/configs/acl.ini'];
        foreach ($iniPaths as $i => $path) {
            $content = file_get_contents(APPLICATION_PATH . $path);
            $content = str_replace('G_Model_CropTemplate', 'Garp_Model_Db_CropTemplate', $content);
            file_put_contents(APPLICATION_PATH . $path, $content);
        }
    }

    protected function _modifyModelFile($finfo) {
        $path = $finfo->getPath() . DIRECTORY_SEPARATOR . $finfo->getFilename();
        $contents = file_get_contents($path);
        if (strpos($contents, 'extends G_Model_') === false) {
            return;
        }
        $contents = str_replace('extends G_Model_', 'extends Garp_Model_Db_', $contents);
        return file_put_contents($path, $contents);
    }
}
