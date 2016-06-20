<?php
/**
 * Garp_Model_Db_AuthOpenId
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Db_AuthOpenId extends Model_Base_AuthOpenId {
    protected $_name = 'authopenid';

    public function init() {
        parent::init();
        $this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
    }

    /**
     * Store a new user. This creates a new auth_openid record, but also
     * a new users record.
     * @param String $openid
     * @param Array $props Properties fetched thru Sreg
     * @return Garp_Db_Table_Row The new user data
     */
    public function createNew($openid, array $props) {
        // first save the new user
        $userModel = new Model_User();
        $userId = $userModel->insert($props);
        $userData = $userModel->find($userId)->current();
        $this->insert(array(
            'openid'    => $openid,
            'user_id'   => $userId
        ));

        $this->getObserver('Authenticatable')->updateLoginStats($userId);
        return $userData;
    }
}
