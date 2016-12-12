<?php
/**
 * Garp_Model_Db_Passwordless
 * class description
 *
 * @package Garp_Model_Db
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Db_AuthPasswordless extends Model_Base_AuthPasswordless {
    protected $_name = 'authpasswordless';

    public function init() {
        parent::init();
        $this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
    }

    public function fetchByUserId($userId) {
        return $this->fetchRow($this->select()->where('user_id = ?', $userId));
    }
}

