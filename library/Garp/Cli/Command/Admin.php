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
			$promptData = array();

			// Pull required fields from Spawner config
			$configDir = APPLICATION_PATH."/modules/default/models/config/";
			$modelSet = new Garp_Model_Spawn_Model_Set(
				new Garp_Model_Spawn_Config_Model_Set(
					new Garp_Model_Spawn_Config_Storage_File($configDir, 'json'),
					new Garp_Model_Spawn_Config_Format_Json
				)
			);
			$userModelConfig = $modelSet['User'];
			$requiredFields = $userModelConfig->fields->getFields('required', true);
			foreach ($requiredFields as $field) {
				if ($field->origin == 'config' && $field->name !== 'id') {
					$promptData[] = $field->name;
				} elseif ($field->origin == 'relation') {
					Garp_Cli::errorOut('Field '.$field->name.' is required but must be filled by way of relation. '.
						'This makes it impossible to create an admin from the commandline.');
				}
			}

			if (!in_array($ini['adapters']['db']['identityColumn'], $promptData)) {
				$promptData[] = $ini['adapters']['db']['identityColumn'];
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
			try {
				$id = $user->insert($newUserData);
				$authLocal = new G_Model_AuthLocal();
				$newAuthLocalData['user_id'] = $id;
				if ($authLocal->insert($newAuthLocalData)) {
					Garp_Cli::lineOut('Successfully created the administrator. (id: '.$id.')');
				} else {
					Garp_Cli::errorOut('Error: could not create administrator.');
				}
			} catch (Zend_Db_Statement_Exception $e) {
				if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email_unique') !== false) {
					Garp_Cli::errorOut('Error: this email address is already in use. Maybe you meant to use Garp Admin make?');
				} else {
					throw $e;
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
			$id = Garp_Cli::prompt('What is the id or email address of the user?');
		}
		$select = $userModel->select();
		if (is_numeric($id)) {
			$filterColumn = 'id';
		} else {
			$filterColumn = 'email';
		}
		$select->where($filterColumn.' = ?', $id);
		$user = $userModel->fetchRow($select);
		if (!$user) {
			Garp_Cli::errorOut('Error: could not find user with '.$filterColumn.' '.$id);
		} else {
			$user->role = 'admin';
			if ($user->save()) {
				// For completeness sake, check if the user has an AuthLocal 
				// record. We disregard the fact wether the user already has any 
				// of the other Auth- records.
				$authLocalModel = new G_Model_AuthLocal();
				$authLocalRecord = $authLocalModel->fetchRow($authLocalModel->select()->where('user_id = ?', $user->id));
				if (!$authLocalRecord) {
					$newAuthLocalData = array(
						'password' => trim(Garp_Cli::prompt('Choose a password:')),
						'user_id'  => $user->id
					);
					$authLocalModel->insert($newAuthLocalData);
				}
				Garp_Cli::lineOut('User with '.$filterColumn.' '.$id.' is now administrator');
			} else {
				Garp_Cli::errorOut('Error: could not make user with '.$filterColumn.' '.$id.' administrator');
			}
		}
	}


	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('Add a new admin:');
		Garp_Cli::lineOut('  g Admin add');
		Garp_Cli::lineOut('');
		Garp_Cli::lineOut('Make an existing user admin:');
		Garp_Cli::lineOut('  g Admin make');
		Garp_Cli::lineOut('');
	}
}
