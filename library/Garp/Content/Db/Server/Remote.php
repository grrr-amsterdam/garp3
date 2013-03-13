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
	 * @var 	Resource  $_sshSession	A session handler, as returned by ssh2_connect()
	 */	
	protected $_sshSession;

	/**
	 * @var Array $_deployParams Deployment parameters containing values for 'server', 'user' and 'deploy_to' path.
	 */
	protected $_deployParams;


	/**
	 * @param String $_environment The environment this server runs in.
	 */
	public function __construct($environment) {
		parent::__construct($environment);
		
		$this->_checkPlatformRequirements();
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
	 * @return 	Resource 	A session handler, as returned by ssh2_connect()
	 */
	public function getSshSession() {
		if (!$this->_sshSession) {
			$this->setSshSession($this->_openSshSession());
		}
		return $this->_sshSession;
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
	}

	/**
	 * @param Resource $sshSession A session handler, as returned by ssh2_connect()
	 */
	public function setSshSession($sshSession) {
		$this->_sshSession = $sshSession;
	}

	/**
	 * @param String $command Shell command
	 */
	public function shellExecString($commandString) {
		$sshSession 	= $this->getSshSession();

		if ($stream = ssh2_exec($sshSession, $commandString)) {
			stream_set_blocking($stream, true);
			$content = stream_get_contents($stream);		
			$this->_interceptErrors($stream);
			fclose($stream);

			return $content;
		}

		return false;
	}
	
	protected function _interceptErrors($sshStream) {
		$errorStream = ssh2_fetch_stream($sshStream, SSH2_STREAM_STDERR);
		stream_set_blocking($errorStream, true);
		$error = stream_get_contents($errorStream);
		fclose($errorStream);

		if ($error) {
			throw new Exception($error);
		}
	}
	
	/**
	 * Stores data in a file.
	 * @param String $path Absolute path within the server to a file where the data should be stored.
	 * @param String $data The data to store.
	 * @return Boolean		Success status of the storage process.
	 */
	public function store($path, $data) {
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
	 * @return Resource $sshSession A session handler, as returned by ssh2_connect()
	 */
	protected function _openSshSession() {
		$host = $this->getHost();
		$session = ssh2_connect($this->getHost(), 22, array('hostkey' => 'ssh-dss'));

		if ($session) {
			if (ssh2_auth_agent($session, $this->getUser())) {
				return $session;
			} else throw new Exception("Could not authenticate a session to {$host} using the SSH agent.");
		} else throw new Exception("Could not connect to {$host}.");
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
	
	protected function _checkPlatformRequirements() {
		if (!function_exists('ssh2_connect')) {
			throw new Exception(
				"The required PECL ssh2 extension is not installed.\n"
				."Usually, 'sudo pecl install ssh2' should be enough,\n"
				."But since the package is currently in beta, you can use:\n"
				."sudo pecl install channel://pecl.php.net/ssh2-0.12\n\n"
			);
		}

		if (!function_exists('ssh2_auth_agent')) {
			throw new Exception(
				"The ssh2 extension is compiled with libssh >= 1.2.3\n"
				."to enable ssh2_auth_agent()."
			);
		}
	}

}