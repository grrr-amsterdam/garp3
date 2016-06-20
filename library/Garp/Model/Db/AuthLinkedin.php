<?php
/**
 * Garp_Model_Db_AuthLinkedin
 * class description
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Db_AuthLinkedin extends Model_Base_AuthLinkedin {
    protected $_name = 'authlinkedin';

    public function init() {
        parent::init();
        $this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
    }

    /**
     * Store a new user. This creates a new auth_linkedin record, but also
     * a new user record.
     * @param String $linkedinId LinkedIn user id
     * @param Array $props Properties received from LinkedIn
     * @return Garp_Db_Table_Row The new user data
     */
    public function createNew($linkedinId, array $props) {
        //print($linkedinId . '<pre>' . print_r($props, true));
        // first save the new user
        $userModel = new Model_User();
        $userId    = $userModel->insert($props);
        $userData  = $userModel->find($userId)->current();
        $this->insert(array(
            'linkedin_uid' => $linkedinId,
            'user_id' => $userId
        ));

        $this->getObserver('Authenticatable')->updateLoginStats($userId);
        return $userData;
    }

}
