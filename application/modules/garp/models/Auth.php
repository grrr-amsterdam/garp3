<?php
/**
 * G_Model_Auth
 * Blueprint for auth models.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
abstract class G_Model_Auth extends Garp_Model_Db {
	/**
	 * Referencemap contains relation info.
	 * @var Data
	 */
	protected $_referenceMap = array(
		'Users' => array(
			'columns' => 'user_id',
			'refTableClass' => 'Model_User',
			'refColumns' => 'id'
		)
	);
	
	
	/**
	 * Initialize observers
	 * @return Void
	 */
	public function init() {
		$this->registerObserver(new Garp_Model_Behavior_Timestampable());
		parent::init();
	}
	
	
	/**
	 * Update login statistics, like IP address and the current date
	 * @param Int $userIdd The user_id value
	 * @return Int The number of rows updated.
	 */
	public function updateLoginStats($userId) {		
		return $this->update(
			array(
				'ip_address' => $_SERVER['REMOTE_ADDR'],
				'last_login' => new Zend_Db_Expr('NOW()')
			),
			$this->getAdapter()->quoteInto('user_id = ?', $userId)
		);
	}
}