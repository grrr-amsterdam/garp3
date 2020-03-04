<?php
/**
 * Garp_Model_Behavior_Authorable
 * Fills the 'author_id' column with the user id of the person executing the write action.
 *
 * @package Garp_Model_Behavior
 * @author David Spreekmeester <david@grrr.nl>
 */
class Garp_Model_Behavior_Authorable extends Garp_Model_Behavior_Abstract {
    const _AUTHOR_COLUMN = 'author_id';
    const _MODIFIER_COLUMN = 'modifier_id';

    protected $_columns;

    protected function _setup($config) {
        if (!isset($config['authorField'])) {
            $config['authorField'] = self::_AUTHOR_COLUMN;
        }
        if (!isset($config['modifierField'])) {
            $config['modifierField'] = self::_MODIFIER_COLUMN;
        }
        $this->_columns = $config;
    }

    /**
     * Before fetch callback.
     *
     * @param array $args
     * @return void
     */
    public function beforeFetch(array &$args) {
        $model = $args[0];
        $select = &$args[1];
        if (!$model->isCmsContext()) {
            return;
        }
        if (!Garp_Auth::getInstance()->isAllowed(get_class($model), 'fetch')
            && Garp_Auth::getInstance()->isAllowed(get_class($model), 'fetch_own')
        ) {
            $currentUserData = Garp_Auth::getInstance()->getUserData();
            $currentUserId   = $currentUserData['id'];
            $select->where($this->_columns['authorField'] . ' = ?', $currentUserId);
        }
    }

    /**
     * Before insert callback. Manipulate the new data here.
     *
     * @param array $args
     * @return array
     */
    public function beforeInsert(array &$args) {
        $data  = &$args[1];

        $auth = Garp_Auth::getInstance();
        if ($auth->isLoggedIn()) {
            $userData = $auth->getUserData();
            $data[$this->_columns['authorField']] = $userData['id'];
        }
    }

    public function beforeUpdate(&$args) {
        $data = &$args[1];

        $auth = Garp_Auth::getInstance();
        if ($auth->isLoggedIn()) {
            $userData = $auth->getUserData();
            $data[$this->_columns['modifierField']] = $userData['id'];
        }
    }

}

