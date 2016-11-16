<?php
/**
 * Garp_Cli_Command_Models
 * class description
 *
 * @package Garp_Cli_Command
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Cli_Command_Models extends Garp_Cli_Command {

    /**
     * Interactively insert a new record
     *
     * @param array $args
     * @return bool
     */
    public function insert($args) {
        if (empty($args)) {
            Garp_Cli::errorOut('Please provide a model');
            return false;
        }

        $className = "Model_{$args[0]}";
        $model = new $className();
        $fields = $model->getConfiguration('fields');
        $fields = array_filter($fields, not(propertyEquals('name', 'id')));

        $mode = $this->_getInsertionMode();
        if ($mode === 'g') {
            $newData = $this->_createGibberish($fields);
        } else {
            $newData = array_reduce(
                $fields,
                function ($acc, $cur) {
                    $acc[$cur['name']] = Garp_Cli::prompt($cur['name']) ?: null;
                    return $acc;
                },
                array()
            );
        }

        $id = $model->insert($newData);
        Garp_Cli::lineOut("Record created: #{$id}");
        return true;
    }

    /**
     * Garp models have moved from the G_Model namespace to the Garp_Model_Db_ namespace.
     * This command migrates extended models to the new namespace.
     *
     * @return bool
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
        return true;
    }

    /**
     * Change references to G_Model_CropTemplate
     *
     * @return void
     */
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

    protected function _createGibberish($fields) {
        $faker = new Garp_Model_Db_Faker();
        return $faker->createFakeRow($fields);
    }

    protected function _getInsertionMode() {
        $query = 'Want to create a record (i)nteractively or shall I just ' .
            'insert a bunch of (g)ibberish?';
        $response = Garp_Cli::prompt($query);
        if (in_array($response, array('i', 'g'))) {
            return $response;
        }
        Garp_Cli::lineOut('Please answer "i" or "g"');
        return _getInsertionMode();
    }
}
