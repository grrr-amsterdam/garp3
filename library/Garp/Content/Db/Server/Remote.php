<?php
/**
 * Garp_Content_Db_Server_Remote
 *
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Db_Server_Remote extends Garp_Content_Db_Server_Abstract {
	const PATH_BACKUP = '/shared/backup/db';

	/**
	 * @var Garp_Shell_RemoteSession $_session
	 */
	protected $_session;

	/**
	 * @var Array $_deployParams 		Deployment parameters containing values for 'server', 'user' and 'deploy_to' path.
	 */
	protected $_deployParams;


	/**
	 * @param String $environment 		The environment this server runs in.
	 * @param String $otherEnvironment 	The environment of the counterpart server
	 * 									(i.e. target if this is source, and vice versa).
	 */
	public function __construct($environment, $otherEnvironment) {
		parent::__construct($environment, $otherEnvironment);

		$this->_verifyCapified();
	}

	/**
	 * @return String The SSH user id to use in connecting
	 */
	public function getUser() {
		$deployParams = $this->getDeployParams();
		return $deployParams['user'];
	}

	/**
	 * @return String The SSH host to connect to
	 */
	public function getHost() {
		$deployParams = $this->getDeployParams();
		return $deployParams['server'];
	}

	public function getRemotePath() {
		$deployParams = $this->getDeployParams();
		return $deployParams['deploy_to'];
	}

	/**
	 * @return Garp_Shell_RemoteSession
	 */
	public function getSession() {
		if (!$this->_session) {
			$session = new Garp_Shell_RemoteSession($this->getHost(), $this->getUser());
			$this->setSession($session);
		}

		return $this->_session;
	}

	/**
	 * @return Resource
	 */
	public function getSshSession() {
		$session = $this->getSession();
		return $session->getSshSession();
	}

	/**
	 * @return Array
	 */
	public function getDeployParams() {
		if (!$this->_deployParams) {
			$this->setDeployParams($this->_fetchDeployParams());
		}
		return $this->_deployParams;
	}

	public function getBackupDir() {
		$backupPath = $this->getRemotePath() . self::PATH_BACKUP;
		return $backupPath;
	}

	/**
	 * @param Array Deployment parameters containing values for 'server', 'user' and 'deploy_to' path.
	 */
	public function setDeployParams(array $deployParams) {
		$this->_deployParams = $deployParams;
		return $this;
	}

	/**
	 * @param Garp_Shell_RemoteSession $session
	 */
	public function setSession(Garp_Shell_RemoteSession $session) {
		$this->_session = $session;
		return $this;
	}

	/**
	 * @param Garp_Shell_Command_Protocol $command Shell command
	 */
	public function shellExec(Garp_Shell_Command_Protocol $command) {
		$session 	= $this->getSession();
		return $command->executeRemotely($session);
	}

	/**
	 * Stores data in a file.
	 * @param String $path Absolute path within the server to a file where the data should be stored.
	 * @param String &$data The data to store.
	 * @return Boolean		Success status of the storage process.
	 */
	public function store($path, &$data) {
		$ssh = $this->getSshSession();
		$sftp = ssh2_sftp($ssh);

		$sftpStream = @fopen('ssh2.sftp://' . $sftp . $path, 'wb');

	    if (!$sftpStream) {
	        throw new Exception("Could not open remote file: $path");
	    }

	    if (@fwrite($sftpStream, $data) === false) {
	        throw new Exception("Could not store {$path} by SFTP on " . $this->getEnvironment());
	    }

	    fclose($sftpStream);
		return true;
	}

	/**
	 * Fetches the SSH deploy parameters for the environment which this storage instance runs on.
	 * @return Array Deployment parameters containing values for 'server', 'user' and 'deploy_to' path.
	 */
	protected function _fetchDeployParams() {
		$deployConfig = new Garp_Deploy_Config();
		$deployParams = $deployConfig->getParams($this->getEnvironment());
		return $deployParams;
	}


	protected function _verifyCapified() {
		$deployConfig = new Garp_Deploy_Config();
		$environment = $this->getEnvironment();

		if (!$deployConfig->isConfigured($environment)) {
			throw new Exception("Could not find deploy information for {$environment}.");
		}
	}

}
