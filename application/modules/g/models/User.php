<?php
/**
 * G_Model_User
 * Standard implementation of a User model.
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @lastmodified $Date: $
 */
class G_Model_User extends Model_Base_User {
	/**
 	 * Role column
 	 * @var String
 	 */
	const ROLE_COLUMN = 'role';


	/**
 	 * Password column
 	 * @var String
 	 */
	const PASSWORD_COLUMN = 'password';


	/**
 	 * Table
 	 * @var String
 	 */
	protected $_name = 'User';


	/**
 	 * A password might be passed, but that belongs in G_Model_AuthLocal.
 	 * Since no primary key exists yet beforeInsert, save the password beforeInsert here,
 	 * and read it again afterInsert.
 	 * @var String
 	 */
	protected $_password;


	/**
 	 * Wether to validate email address afterUpdate. beforeUpdate there is a check
 	 * to see if the email address actually changes.
 	 * @var Boolean
 	 */
	protected $_validateEmail;


	/**
 	 * BeforeInsert callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeInsert(array &$args) {
		$data = &$args[1];

		// Prevent admins from saving a user's role greater than their own.
		if (!empty($data[self::ROLE_COLUMN]) && !$this->_isRoleAllowed($data[self::ROLE_COLUMN])) {
			throw new Garp_Model_Exception('You are not allowed to assign a role greater than your own.');
		}

		// A password might be passed along, but that is actually a column of G_Model_AuthLocal
		if (!empty($data[self::PASSWORD_COLUMN])) {
			// Save it for later, for reading from afterInsert
			$this->_password = $data[self::PASSWORD_COLUMN];
		}
		// Remove the password key from the data to prevent an error
		unset($data[self::PASSWORD_COLUMN]);
	}


	/**
 	 * AfterInsert callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function afterInsert(array &$args) {
		$data = $args[1];
		$primaryKey = &$args[2];		

		// Check if an email address was changed. If so, send a validation email		
		if (array_key_exists('email', $data) && !empty($data['email'])) {
			$this->_onEmailChange($data['email']);
		}

		// Save the password that was stored in beforeInsert
		if ($this->_password) {
			$authLocalModel = new G_Model_AuthLocal();
			$newAuthLocalData = array(
				'password' => $this->_password,
				'user_id'  => $primaryKey
			);
			// Save the AuthLocal record
			$authLocalModel->insert($newAuthLocalData);
		}
	}

	
	/**
 	 * BeforeUpdate callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeUpdate(array &$args) {
		$data = &$args[1];
		$where = $args[2];

		// Check if the email address is about to be changed
		if (array_key_exists('email', $data)) {
			// Collect the current email addresses to see if they are to be changed
			// @todo For now we assume that email is a unique value. This means that 
			// we use fetchRow instead of fetchAll. 
			// If this ever changes, fix this code.
			$user = $this->fetchRow(
				$this->select()->from($this->getName(), array('email'))->where($where)
			);
			if ($user && $user->email != $data['email']) {
				$this->_validateEmail = true;
			}
		}

		// A password might be passed in, and needs to be passed to G_Model_AuthLocal
		if (!empty($data[self::PASSWORD_COLUMN])) {
			// $primaryKey = $this->info(self::PRIMARY);
			// @note 'id' is the only valid primary key here. This might not be flexible enough
			// in the future, in that case, use $this->info(self::PRIMARY) to fetch the primary key.
			$primaryKey = 'id';
			// Find all matches and create or update an AuthLocal record for them.
			$matchedRecords = $this->fetchAll(
				$this->select()->from($this->getName(), array($primaryKey))->where($where)
			);

			foreach ($matchedRecords as $matchedRecord) {
				$thePrimaryKey = $matchedRecord->{$primaryKey};
				$authLocalModel = new G_Model_AuthLocal();
				// Check if an AuthLocal record already exists
				$authLocalRecord = $authLocalModel->fetchRow(
					$authLocalModel->select()->where('user_id = ?', $thePrimaryKey)
				);
				// If not, create a new one
				if (!$authLocalRecord) {
					$authLocalModel->insert(array(
						'user_id' => $thePrimaryKey,
						'password' => $data[self::PASSWORD_COLUMN]
					));
				} else {
					$authLocalRecord->{self::PASSWORD_COLUMN} = $data[self::PASSWORD_COLUMN];
					$authLocalRecord->save();
				}
			}

			// Remove the password key from the data to prevent an error
			unset($data[self::PASSWORD_COLUMN]);
		}

		// If the role is not part of the data, fetch it live
		$exception = 'You are not allowed to edit users with a role greater than your own.';
		if (empty($data[self::ROLE_COLUMN])) {
			$rows = $this->fetchAll($where);
			foreach ($rows as $row) {
				if (!$this->_isRoleAllowed($row->{self::ROLE_COLUMN})) {
					throw new Garp_Model_Exception($exception);
				}
			}
		} else {
			// Prevent admins from saving a user's role greater than their own.
			if (!$this->_isRoleAllowed($data[self::ROLE_COLUMN])) {
				throw new Garp_Model_Exception($exception);
			}
		}
	}


	/**
 	 * AfterUpdate callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function afterUpdate(array &$args) {
		// Check if an email address was changed. If so, send a validation email
		$data = $args[2];
		if (array_key_exists('email', $data) && $this->_validateEmail) {
			$this->_onEmailChange($data['email'], 'update');
		}
	}


	/**
 	 * BeforeDelete callback
 	 * @param Array $args
 	 * @return Void
 	 */
	public function beforeDelete(array &$args) {
		$where = $args[1];
		$exception = 'You are not allowed to delete users with a role greater than your own.';
		$rows = $this->fetchAll($where);
		foreach ($rows as $row) {
			if (!$this->_isRoleAllowed($row->{self::ROLE_COLUMN})) {
				throw new Garp_Model_Exception($exception);
			}
		}
	}


	/**
 	 * Respond to change in email address
 	 * @param String $email The new email address
 	 * @param String $updateOrInsert Wether this was caused by an insert or an update
 	 * @return Void
 	 */
	protected function _onEmailChange($email, $updateOrInsert = 'insert') {
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();

		// See if validation of email is enabled
		if (!empty($authVars['validateEmail']['enabled']) && $authVars['validateEmail']['enabled']) {
			$validationTokenColumn = $authVars['validateEmail']['token_column'];
			$emailValidColumn = $authVars['validateEmail']['email_valid_column'];

			// Fetch fresh user data by email
			$users = $this->fetchAll($this->select()->from($this->getName(), array('id', 'email', $validationTokenColumn, $emailValidColumn))->where('email = ?', $email));
			// Generate validation token for all the found users
			foreach ($users as $user) {
				$this->invalidateEmailAddress($user, $updateOrInsert);
			}
		}
	}


	/**
 	 * Start the email validation procedure
 	 * @param Garp_Db_Table_Row $user
 	 * @param String $updateOrInsert Wether this was caused by an insert or an update
 	 * @return Boolean Wether the procedure succeeded
 	 */
	public function invalidateEmailAddress(Garp_Db_Table_Row $user, $updateOrInsert = 'insert') {
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		$validationTokenColumn = $authVars['validateEmail']['token_column'];
		$emailValidColumn = $authVars['validateEmail']['email_valid_column'];
		
		// Generate the validation code
		$validationToken = uniqid();
		$validationCode = $this->generateEmailValidationCode($user, $validationToken);
		
		// Store the token in the user record
		$user->{$validationTokenColumn} = $validationToken;
		// Invalidate the user's email
		$user->{$emailValidColumn} = 0;

		if ($user->save()) {
			return $this->sendEmailValidationEmail($user, $validationCode, $updateOrInsert);
		}
		return false;
	}


	/**
 	 * Generate unique email validation code for a user
 	 * @param Garp_Db_Table_Row $user
 	 * @param String $validationToken Unique random value
 	 * @return String
 	 */
	public function generateEmailValidationCode(Garp_Db_Table_Row $user, $validationToken) {
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();

		$validationCode  = '';
		$validationCode .= $validationToken;
		$validationCode .= md5($user->email);
		$validationCode .= md5($authVars['salt']);
		$validationCode .= md5($user->id);
		$validationCode = md5($validationCode);
		return $validationCode;
	}


	/**
 	 * Send validation email to the user
 	 * @param Garp_Db_Table_Row $user The user
 	 * @param String $code The validation code
 	 * @param String $updateOrInsert Wether this was caused by an insert or an update
 	 * @return Boolean
 	 */
	public function sendEmailValidationEmail(Garp_Db_Table_Row $user, $code, $updateOrInsert = 'insert') {
		$auth = Garp_Auth::getInstance();
		$authVars = $auth->getConfigValues();
		
		// Render the email message
		$activationUrl = '/g/auth/validateemail/c/'.$code.'/e/'.md5($user->email).'/';

		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
		$view = $bootstrap->getResource('View');
		$emailMessage = $view->partial($authVars['validateEmail']['email_partial'], 'default', array(
			'user' => $user,
			'activationUrl' => $activationUrl,
			'updateOrInsert' => $updateOrInsert
		));

		// Note: this requires SES credentials defined in amazon.ses.accessKey and amazon.ses.secretKey
		$ses = new Garp_Service_Amazon_Ses();
		$response = $ses->sendEmail(array(
			'Destination' => $user->email,
			'Message'     => $emailMessage,
			'Subject'     => $authVars['validateEmail']['email_subject'],
			'Source'      => $authVars['validateEmail']['email_from_address']
		));
		return $response;
	}


	/**
	 * Override method for the base model equivalent, to use spaces as separators.
	 * This renders a single identifying string for this record, to be used in an sql statement. 
	 * @param String [$tableAlias] Optional table alias. If not provided, the actual table name is used.
	 * @return String Sql statement to be used in a select query.
	 */
	public function getRecordLabelSql($tableAlias = null) {
		$tableAlias = $tableAlias ?: 'User';
		return "CONVERT(CONCAT_WS(' ', IF(`{$tableAlias}`.`first_name` <> \"\", `{$tableAlias}`.`first_name`, NULL), IF(`{$tableAlias}`.`last_name_prefix` <> \"\", `{$tableAlias}`.`last_name_prefix`, NULL), IF(`{$tableAlias}`.`last_name` <> \"\", `{$tableAlias}`.`last_name`, NULL)) USING utf8)";
	}


	/**
 	 * Prevent admins from saving a user's role greater than their own. 
 	 * Note: will return TRUE if no user is logged in. This is because
 	 * we sometimes have to manipulate roles from apis and cli commands
 	 * where no physical user session is present.
 	 * Will also return TRUE when ACL is not defined.
 	 * @param String $role The role that is about to be saved.
 	 * @return Boolean
 	 */
	protected function _isRoleAllowed($role) {
		$currentAdminRole = Garp_Auth::getInstance()->getCurrentRole();

		$currentAdminIsVisitor = Garp_Auth::DEFAULT_VISITOR_ROLE == $currentAdminRole;
		$zendAclIsNotRegistered = !Zend_Registry::isRegistered('Zend_Acl');
		$roleIsEqualToCurrentAdminRole = $role == $currentAdminRole;

		if ($currentAdminIsVisitor || $zendAclIsNotRegistered || $roleIsEqualToCurrentAdminRole) {
			return true;
		}

		// Check if the role that is about to be manipulated is a child of the 
		// current role. If so, that role is considered greater than the current 
		// role.
		// Note that this logic does not check ACL branches that can be considered
		// siblings, or nephews.
		// For instance Visitor > User > Admin vs Visitor > Teacher. Is teacher greater 
		// or less than Admin? These semantics must be written customly.
		$children = Garp_Auth::getInstance()->getRoleChildren($currentAdminRole);
		return !in_array($role, $children);
	}
}
