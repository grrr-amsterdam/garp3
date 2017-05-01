<?php
/**
 * Garp_Model_Db_User
 * Standard implementation of a User model.
 *
 * @package Garp
 * @author Harmen Janssen <harmen@grrr.nl>
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @lastmodified $Date: $
 */
class Garp_Model_Db_User extends Model_Base_User {
    const EXCEPTION_CANNOT_ASSIGN_GREATER_ROLE 
        = 'You are not allowed to assign a role greater than your own.';
    const EXCEPTION_CANNOT_EDIT_GREATER_ROLE 
        = 'You are not allowed to edit users with a role greater than your own.';


    const ROLE_COLUMN = 'role';
    const PASSWORD_COLUMN = 'password';
    const IMAGE_URL_COLUMN = 'imageUrl';

    /**
     * A password might be passed, but that belongs in Model_AuthLocal.
     * Since no primary key exists yet beforeInsert, save the password beforeInsert here,
     * and read it again afterInsert.
     *
     * @var String
     */
    protected $_password;

    /**
     * Wether to validate email address afterUpdate. beforeUpdate there is a check
     * to see if the email address actually changes.
     *
     * @var Boolean
     */
    protected $_validateEmail;

    public function fetchByEmail($email) {
        return $this->fetchRow($this->select()->where('email = ?', $email));
    }

    /**
     * Grab only session columns by userid
     * 
     * @param int $userId
     * @return Garp_Db_Table_Row
     */
    public function fetchUserForSession($userId) {
        $select = $this->select()
            ->from($this->getName(), Garp_Auth::getInstance()->getSessionColumns())
            ->where('id = ?', $userId);
        return $this->fetchRow($select);
    }

    /**
     * Update user with activation code and expire time.
     * Used when forgot password
     * 
     * @param int $userId
     * @param string $activationCode
     * @param string $activationExpiry
     * @return int updated rows
     */
    public function updateUserWithActivationCode($userId, $activationCode, $activationExpiry) {
        $authVars = Garp_Auth::getInstance()->getConfigValues('forgotpassword');
        $expiresColumn = $authVars['activation_code_expiration_date_column'];
        $tokenColumn   = $authVars['activation_token_column'];

        $quotedUserId = $this->getAdapter()->quote($userId);
        return $this->update(
            array(
            $expiresColumn => $activationExpiry,
            $tokenColumn => $activationCode
            ), "id = $quotedUserId"
        );
    }

    /**
     * BeforeInsert callback
     *
     * @param Array $args
     * @return Void
     */
    public function beforeInsert(array &$args) {
        $data = &$args[1];

        if (array_key_exists(self::IMAGE_URL_COLUMN, $data) 
            && !is_null($data[self::IMAGE_URL_COLUMN])
        ) {
            // Allow passing in of image URLs. These are downloaded and added as image_id
            $data['image_id'] = $this->_grabRemoteImage($data[self::IMAGE_URL_COLUMN]);
        }
        unset($data[self::IMAGE_URL_COLUMN]);

        // Prevent admins from saving a user's role greater than their own.
        if (!empty($data[self::ROLE_COLUMN]) && !$this->_isRoleAllowed($data[self::ROLE_COLUMN])) {
            throw new Garp_Model_Exception(self::EXCEPTION_CANNOT_ASSIGN_GREATER_ROLE);
        }

        // A password might be passed along, but that is actually a column of Model_AuthLocal
        if (!empty($data[self::PASSWORD_COLUMN])) {
            // Save it for later, for reading from afterInsert
            $this->_password = $data[self::PASSWORD_COLUMN];
        }
        // Remove the password key from the data to prevent an error
        unset($data[self::PASSWORD_COLUMN]);

        // Prefill user columns from pre-defined config
        $data = $this->getPrefilledData($data);
    }

    /**
     * AfterInsert callback
     *
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

        // Save the password that was stored in beforeInsert()
        if ($this->_password) {
            $authLocalModel = new Model_AuthLocal();
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
     *
     * @param Array $args
     * @return Void
     */
    public function beforeUpdate(array &$args) {
        $data = &$args[1];
        $where = $args[2];
        $authVars = Garp_Auth::getInstance()->getConfigValues('validateemail');

        // Check if the email address is about to be changed, and wether we should respond to it
        if ((!empty($authVars['enabled']) && $authVars['enabled']) 
            && array_key_exists('email', $data)
        ) {
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

        if (array_key_exists(self::IMAGE_URL_COLUMN, $data)) {
            // Allow passing in of image URLs. These are downloaded and added as image_id
            $data['image_id'] = $this->_grabRemoteImage($data[self::IMAGE_URL_COLUMN]);
            unset($data[self::IMAGE_URL_COLUMN]);
        }

        // A password might be passed in, and needs to be passed to Model_AuthLocal
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
                $authLocalModel = new Model_AuthLocal();
                // Check if an AuthLocal record already exists
                $authLocalRecord = $authLocalModel->fetchRow(
                    $authLocalModel->select()->where('user_id = ?', $thePrimaryKey)
                );
                // If not, create a new one
                if (!$authLocalRecord) {
                    $authLocalModel->insert(
                        array(
                        'user_id' => $thePrimaryKey,
                        'password' => $data[self::PASSWORD_COLUMN]
                        )
                    );
                } else {
                    $authLocalRecord->{self::PASSWORD_COLUMN} = $data[self::PASSWORD_COLUMN];
                    $authLocalRecord->save();
                }
            }

            // Remove the password key from the data to prevent an error
            unset($data[self::PASSWORD_COLUMN]);
        }

        // If the role is not part of the data, fetch it live
        if (empty($data[self::ROLE_COLUMN])) {
            $rows = $this->fetchAll($where);
            foreach ($rows as $row) {
                if (!$this->_isRoleAllowed($row->{self::ROLE_COLUMN})) {
                    throw new Garp_Model_Exception(self::EXCEPTION_CANNOT_EDIT_GREATER_ROLE);
                }
            }
        } else {
            // Prevent admins from saving a user's role greater than their own.
            if (!$this->_isRoleAllowed($data[self::ROLE_COLUMN])) {
                throw new Garp_Model_Exception(self::EXCEPTION_CANNOT_EDIT_GREATER_ROLE);
            }
        }
    }

    /**
     * AfterUpdate callback
     *
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
     *
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
     *
     * @param String $email The new email address
     * @param String $updateOrInsert Wether this was caused by an insert or an update
     * @return Void
     */
    protected function _onEmailChange($email, $updateOrInsert = 'insert') {
        $authVars = Garp_Auth::getInstance()->getConfigValues('validateemail');

        // See if validation of email is enabled
        if (empty($authVars['enabled']) || !$authVars['enabled']) {
            return;
        }
        $validationTokenColumn = $authVars['token_column'];
        $emailValidColumn = $authVars['email_valid_column'];

        // Fetch fresh user data by email
        $users = $this->fetchAll(
            $this->select()->from(
                $this->getName(),
                array('id', 'email', $validationTokenColumn, $emailValidColumn)
            )
                ->where('email = ?', $email)
        );

        // Generate validation token for all the found users
        foreach ($users as $user) {
            $this->invalidateEmailAddress($user, $updateOrInsert);
        }
    }

    /**
     * Start the email validation procedure
     *
     * @param Garp_Db_Table_Row $user
     * @param String $updateOrInsert Wether this was caused by an insert or an update
     * @return Boolean Wether the procedure succeeded
     */
    public function invalidateEmailAddress(Garp_Db_Table_Row $user, $updateOrInsert = 'insert') {
        $authVars = Garp_Auth::getInstance()->getConfigValues('validateemail');
        $validationTokenColumn = $authVars['token_column'];
        $emailValidColumn = $authVars['email_valid_column'];

        // Generate the validation code
        $validationToken = uniqid();
        $validationCode = $this->generateEmailValidationCode($user, $validationToken);

        if (!$user->isConnected()) {
            $user->setTable($this);
        }

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
     *
     * @param Garp_Db_Table_Row $user
     * @param String $validationToken Unique random value
     * @return String
     */
    public function generateEmailValidationCode(Garp_Db_Table_Row $user, $validationToken) {
        $authVars = Garp_Auth::getInstance()->getConfigValues();

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
     *
     * @param Garp_Db_Table_Row $user The user
     * @param String $code The validation code
     * @param String $updateOrInsert Wether this was caused by an insert or an update
     * @return Boolean
     */
    public function sendEmailValidationEmail(
        Garp_Db_Table_Row $user, $code, $updateOrInsert = 'insert'
    ) {
        $authVars = Garp_Auth::getInstance()->getConfigValues('validateemail');

        // Render the email message
        $activationUrl = '/g/auth/validateemail/c/' . $code . '/e/' . md5($user->email) . '/';

        if (!empty($authVars['email_partial'])) {
            $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
            $view = $bootstrap->getResource('View');
            $emailMessage = $view->partial(
                $authVars['email_partial'], 'default', array(
                'user' => $user,
                'activationUrl' => $activationUrl,
                'updateOrInsert' => $updateOrInsert
                )
            );
            $messageParam = 'htmlMessage';
        } else {
            $snippetId = 'validate email ';
            $snippetId .= $updateOrInsert == 'insert' ? 'new user' : 'existing user';
            $snippetId .= ' email';
            $emailMessage = __($snippetId);
            $emailMessage = Garp_Util_String::interpolate(
                $emailMessage, array(
                'USERNAME' => (string)new Garp_Util_FullName($user),
                'ACTIVATION_URL' => (string)new Garp_Util_FullUrl($activationUrl)
                )
            );
            $messageParam = 'message';
        }

        $mailer = new Garp_Mailer();
        return $mailer->send(
            array(
            'to' => $user->email,
            'subject' => __($authVars['email_subject']),
            $messageParam => $emailMessage
            )
        );
    }

    /**
     * Prevent admins from saving a user's role greater than their own.
     * Note: will return TRUE if no user is logged in. This is because
     * we sometimes have to manipulate roles from apis and cli commands
     * where no physical user session is present.
     * Will also return TRUE when ACL is not defined.
     *
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

    protected function _grabRemoteImage($imageUrl, $filename = null) {
        $image = new Model_Image();
        $imageId = $image->insertFromUrl($imageUrl, $filename);
        return $imageId;
    }

    public function getPrefilledData(array $data) {
        if (!isset(Zend_Registry::get('config')->auth->users) 
            || !array_key_exists('email', $data) 
            || is_null($data['email'])
        ) {
            return $data;
        }
        $userData = Zend_Registry::get('config')->auth->users;
        $prefilledRecordsForUser = array_filter(
            $userData->toArray(), function ($item) use ($data) {
                return isset($item['email']) && $item['email'] == $data['email'];
            }
        );
        if (!count($prefilledRecordsForUser)) {
            return $data;
        }

        // Note the submitted data takes precedence
        $prefilledData = call_user_func_array('array_merge', $prefilledRecordsForUser);
        return array_merge($prefilledData, $data);
    }

    /**
     * Strip an array of columns that are not part of this model.
     * This overrides Garp_Model_Db::filterColumns because the user model accepts some foreign
     * columns.
     *
     * @param Array $data
     * @return Array
     */
    public function filterColumns(array $data) {
        $testCols = array_fill_keys($this->info(Zend_Db_Table_Abstract::COLS), null);
        $testCols[self::PASSWORD_COLUMN] = null;
        $testCols[self::IMAGE_URL_COLUMN] = null;
        return array_intersect_key($data, $testCols);
    }

}
