<?php
/**
 * @package Garp_Spawn_Model
 * @author  David Spreekmeester <david@grrr.nl>
 */
class Garp_Spawn_Model_I18n extends Garp_Spawn_Model_Abstract {

    public function __construct(Garp_Spawn_Config_Model_I18n $config) {
        parent::__construct($config);
    }

    public function isTranslated(): bool {
        return true;
    }

    public function getTableClassName(): string {
        return 'Garp_Spawn_Db_Table_I18n';
    }

}
