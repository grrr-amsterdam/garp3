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
	 * @param Int $userId The user_id value
	 * @param Array $columns Extra columns, variable
	 * @return Int The number of rows updated.
	 */
	public function updateLoginStats($userId, $columns = array()) {
		$columns['ip_address'] = $_SERVER['REMOTE_ADDR'];
		$columns['last_login'] = new Zend_Db_Expr('NOW()');
		return $this->update(
			$columns,
			$this->getAdapter()->quoteInto('user_id = ?', $userId)
		);
	}
}
