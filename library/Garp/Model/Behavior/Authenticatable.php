<?php
/**
 * Garp_Model_Behavior_Authenticatable
 * Behavior for shared behavior between auth models
 *
 * @package Garp_Model_Behavior
 * @author  Harmen Janssen <harmen@grrr.nl>
 */
class Garp_Model_Behavior_Authenticatable extends Garp_Model_Behavior_Abstract {

    protected function _setup($config = array()) {
        $this->_model = $config[0];
    }

    /**
     * Update login statistics, like IP address and the current date
     *
     * @param int $userId The user_id value
     * @param array $columns Extra columns, variable
     * @return int The number of rows updated.
     */
    public function updateLoginStats($userId, $columns = array()) {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $columns['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
        $columns['last_login'] = new Zend_Db_Expr('NOW()');
        return $this->_model->update(
            $columns,
            $this->_model->getAdapter()->quoteInto('user_id = ?', $userId)
        );
    }

}
