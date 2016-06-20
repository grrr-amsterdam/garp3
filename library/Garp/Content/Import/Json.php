<?php
/**
 * Garp_Content_Import_Json
 * Import content from JSON file
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Content_Import
 */
class Garp_Content_Import_Json extends Garp_Content_Import_Abstract {
    /**
     * Return some sample data so an admin can provide
     * mapping of columns by example.
     * @return Array
     */
    public function getSampleData() {
        $data = $this->_getJson();
        return array_map(function($datum) {
            return /*array_values(*/array_filter($datum, function($datum) {
                return is_scalar($datum);
            })/*)*/;
        }, array_splice($data, 0, 3));
    }

    /**
     * Insert data from importfile into database
     * @param Garp_Model $model The imported data is for this model
     * @param Array $mapping Mapping of import columns to table columns
     * @param Array $options Various extra import options
     * @return Boolean
     */
    public function save(Garp_Model $model, array $mapping, array $options) {
        $data = $this->_getJson();
        foreach ($data as $i => $datum) {
            try {
                $this->_insert($model, $datum, $mapping);
            } catch (Exception $e) {
                if (!$options['ignoreErrors']) {
                    $this->rollback($model, $pks);
                }
                throw $e;
            }
        }
        return true;
    }

    /**
     * Insert a new row
     * @param Garp_Model $model
     * @param Array $data Collection of data
     * @param Array $mapping Collection of column names
     * @return Mixed primary key
     */
    protected function _insert(Garp_Model $model, array $data, array $mapping) {
        $newData = array();
        // Remove unused mappings
        $mapping = array_filter($mapping);
        foreach ($mapping as $key => $val) {
            $newData[$val] = $data[$key];
        }
        return $model->insert($newData);
    }

    protected function _getJson() {
        $file = new Garp_File(Garp_File::TYPE_DOCUMENTS);
        $data = $file->fetch(basename($this->_importFile));
        return Zend_Json::decode($data);
    }
}
