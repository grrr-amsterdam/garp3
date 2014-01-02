<?php
/**
 * G_Model_AuthLocal
 * Stores login data from local accounts
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class G_Model_AuthLocal extends G_Model_Auth {
	protected $_name = 'auth_local';
	
	
	protected $_referenceMap = array(
		'User' => array(
			'columns' => 'user_id',
			'refTableClass' => 'Model_User',
			'refColumns' => 'id'
		)
	);
	
	
	/**
	 * Try to log a user in.
	 * @param String $identity
	 * @param String $credential
	 * @param Garp_Util_Configuration $authVars
	 * @return Garp_Db_Table_Row
	 */
	public function tryLogin($identity, $credential, Garp_Util_Configuration $authVars) {
		$theOtherModel = new $authVars['model']();
		$theOtherTable = $theOtherModel->getName();
		$select = $this->select()
		  ->setIntegrityCheck(false)
		  ->from($this->_name, array())
		  ->joinInner($theOtherTable, $this->_name.'.user_id = '.$theOtherTable.'.id')
		  ->where($theOtherTable.'.'.$authVars['identityColumn'].' = ?', $identity)
		  ->where($this->_name.'.'.$authVars['credentialColumn'].' = '.
			  $authVars['hashMethod'].'(CONCAT(?, '.
			  $this->getAdapter()->quoteInto('?', $authVars['salt']).
			  '))', $credential)
		  ->order($this->_name.'.id')
		;
		$result = $this->fetchRow($select);
		
		// update stats if we found a match
		if ($result) {
			$this->updateLoginStats($result->id);
		}
		
		return $result;
	}
	
	
	/**
	 * BeforeInsert callback, hashes the password.
	 * @param Array $args
	 * @return Void
	 */
	public function beforeInsert(&$args) {
		$data = &$args[1];
		$data = $this->_hashPassword($data);
	}
	
	
	/**
	 * BeforeUpdate callback, hashes the password.
	 * @param Array $args
	 * @return Void
	 */
	public function beforeUpdate(&$args) {
		$data = &$args[1];
		$data = $this->_hashPassword($data);
	}
	
	
	/**
	 * Hash an incoming password
	 * @param Array $data The new userdata
	 * @return Array The modified userdata
	 */
	protected function _hashPassword(array $data) {
		$config	= Garp_Cache_Ini::factory(APPLICATION_PATH.'/configs/application.ini', APPLICATION_ENV);
		if (!empty($config->auth->adapters->db)) {
			$authVars = $config->auth->adapters->db;
			$credentialColumn = $authVars->credentialColumn;
			$hashMethod		  = $authVars->hashMethod;
			$salt			  = $authVars->salt;
			if (!empty($data[$credentialColumn])) {
				$data[$credentialColumn] = new Zend_Db_Expr($hashMethod.'(CONCAT('.
											$this->getAdapter()->quoteInto('?', $data[$credentialColumn]).
											', '.$this->getAdapter()->quoteInto('?', $salt).'))');
			}
		}
		return $data;
	}
}
