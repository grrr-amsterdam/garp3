<?php
/**
 * Garp_Cli_Command_Admin
 * Create administrators for the project following the local auth method)
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_Admin extends Garp_Cli_Command {
	/**
 	 * Add a new admin to the system
 	 * @param Array $args
 	 * @return Void
 	 */
	public function add(array $args = array()) {
		$ini = Garp_Auth::getInstance()->getConfigValues();
		if (empty($ini['adapters']['db'])) {
			Garp_Cli::errorOut('Error: DB adapter is not configured in application.ini.');
		} elseif (empty($ini['adapters']['db']['identityColumn']) ||
							empty($ini['adapters']['db']['credentialColumn'])) {
			Garp_Cli::errorOut('Error: identityColumn or credentialColumn not configured in application.ini');
		} else {
			$newUserData = array(
				'role' => 'admin'
			);
			$promptData = array(
				$ini['adapters']['db']['identityColumn']
			);

			if (empty($ini['displayField'])) {
				$promptData[] = 'name';
			} else {
				foreach ((array) $ini['displayField'] as $key) {
					$promptData[] = $key;
				}
			}
		
			// prompt for the new data
			Garp_Cli::lineOut('Please fill the following columns:');
			foreach ($promptData as $key) {
				$newUserData[$key] = trim(Garp_Cli::prompt($key.':'));
			}

			$newAuthLocalData = array(
				'password' => trim(Garp_Cli::prompt('Choose a password:'))
			);
			
			/**
		 	 * A lot of assumptions are made here;
		 	 * - a users table is available, as well as a User model
		 	 * - an auth_local table is available, following the conventions set by Garp
		 	 * - the users table has an id, name, email and role column, while the password 
		 	 *   column resides in the auth_local table
		 	 * 
		 	 * While all this is the preferred method, it's entirely possible to circumvent these
		 	 * conventions and come up with project-specific standards. 
		 	 * In that case however, this CLI command is not for you.
		 	 */
			$user = new Model_User();
			if ($id = $user->insert($newUserData)) {
				$authLocal = new G_Model_AuthLocal();
				$newAuthLocalData['user_id'] = $id;
				if ($authLocal->insert($newAuthLocalData)) {
					Garp_Cli::lineOut('Successfully created the administrator.');
				} else {
					Garp_Cli::errorOut('Error: could not create administrator.');
				}
			}
		}
	}


	/**
 	 * Make an existing user admin
 	 * @param Array args
 	 * @return Void
 	 */
	public function make(array $args = array()) {
		$userModel = new Model_User();
		if (!empty($args)) {
			$id = $args[0];
		} else {
			$id = Garp_Cli::prompt('What is the id of the user?');
		}
		$user = $userModel->fetchRow($userModel->select()->where('id = ?', $id));
		if (!$user) {
			Garp_Cli::errorOut('Error: could not find user #'.$id);
		} else {
			$user->role = 'admin';
			if ($user->save()) {
				Garp_Cli::lineOut('User #'.$id.' is now administrator');
			} else {
				Garp_Cli::errorOut('Error: could not make user #'.$id.' administrator');
			}
		}
	}
}
