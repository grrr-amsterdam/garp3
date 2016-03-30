<?php
/**
 * Garp_Model_Db_AuthLocal
 * Stores login data from local accounts
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Db
 * @lastmodified $Date: $
 */
class Garp_Model_Db_AuthLocal extends Model_Base_AuthLocal {
	protected $_name = 'authlocal';

	public function init() {
		parent::init();
		$this->registerObserver(new Garp_Model_Behavior_Authenticatable(array($this)));
	}

	/**
	 * Try to log a user in.
	 * @param String $identity
	 * @param String $credential
	 * @param Garp_Util_Configuration $authVars
	 * @param Array $sessionColumns Which columns to retrieve for storage in the session.
	 * @return Garp_Db_Table_Row
	 * @throws Garp_Auth_Adapter_Db_UserNotFoundException When the user is not found
	 * @throws Garp_Auth_Adapter_Db_InvalidPasswordException When the password does not match
	 */
	public function tryLogin($identity, $credential, Garp_Util_Configuration $authVars, $sessionColumns = null) {
		$theOtherModel = new $authVars['model']();
		$theOtherTable = $theOtherModel->getName();
		if (is_null($sessionColumns)) {
			$sessionColumns = Zend_Db_Select::SQL_WILDCARD;
		}
		$select = $this->select()
		  ->setIntegrityCheck(false)
		  ->from($this->_name, array($authVars['credentialColumn']))
		  ->joinInner($theOtherTable, $this->_name.'.user_id = '.$theOtherTable.'.id', $sessionColumns)
		  ->where($theOtherTable.'.'.$authVars['identityColumn'].' = ?', $identity)
		  ->order($this->_name.'.id')
		;
		$result = $this->fetchRow($select);

		// update stats if we found a match
		if (!$result) {
			throw new Garp_Auth_Adapter_Db_UserNotFoundException();
			return null;
		}
		$foundCredential = $result->{$authVars['credentialColumn']};
		$testCredential = $authVars['hashMethod']($credential.$authVars['salt']);

		if ($foundCredential != $testCredential) {
			throw new Garp_Auth_Adapter_Db_InvalidPasswordException();
		}
		$this->getObserver('Authenticatable')->updateLoginStats($result->id);
		unset($result->{$authVars['credentialColumn']});
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
		$config = Zend_Registry::get('config');
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
