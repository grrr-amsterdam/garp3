<?php
/**
 * Garp_Model_Db_CropTemplate
 * Model for CropTemplates (@see templates in application.ini)
 *
 * @package Garp_Model_Db
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_CropTemplate extends Garp_Model_IniFile {
    /**
     * Which backend ini file to use
     *
     * @var string
     */
    protected $_file = 'application.ini';

    /**
     * Which namespace to use
     *
     * @var string
     */
    protected $_namespace = 'image.template';

    /**
     * Fetch all entries
     *
     * @return array
     */
    public function fetchAll() {
        $templates = parent::fetchAll();
        $out = array();
        $id  = 1;
        foreach ($templates as $key => $value) {
            if (!array_key_exists('richtextable', $value) || !$value['richtextable']) {
                continue;
            }
            $out[] = array(
                'id'    => $id++,
                'name'  => $key,
                'w'     => !empty($value['w']) ? $value['w'] : null,
                'h'     => !empty($value['h']) ? $value['h'] : null,
                'crop'  => !empty($value['crop']) ? $value['crop'] : null,
                'grow'  => !empty($value['grow']) ? $value['grow'] : null
            );
        }
        return $out;
    }

}
