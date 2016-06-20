<?php
/**
 * Garp_Model_Db_Passwordless
 * class description
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp_Model_Db
 */
class Garp_Model_Db_AuthPasswordless extends Model_Base_AuthPasswordless {
    protected $_name = 'authpasswordless';

    public function init() {
        parent::init();
        $this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
    }

}
