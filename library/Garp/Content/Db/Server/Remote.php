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
		
		$this->_verifyCapified();
		
		$this->setDeployParams($this->_fetchDeployParams());
		$this->setSshSession($this->_openSshSession());
	}

	/**
	 * @return String The SSH user id to use in connecting
	 */
	public function getUser() {
		return $this->_deployParams['user'];
	}

	/**
	 * @return String The SSH host to connect to
	 */
	public function getHost() {
		return $this->_deployParams['server'];
	}

	public function getRemotePath() {
		return $this->_deployParams['deploy_to'];
	}

	/**
	 * @return 	Resource 	A session handler, as returned by ssh2_connect()
	 */
	public function getSshSession() {
		return $this->_sshSession;
	}

	/**
	 * @return Array
	 */
	public function getDeployParams() {
		return $this->_deployParams;
	}

	public function getBackupPath() {
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
	public function shellExec($command) {
		$sshSession = $this->getSshSession();
		if ($stream = ssh2_exec($sshSession, $command)) {
			stream_set_blocking($stream, true);
			$output = stream_get_contents($stream);
		} else return false;

		return $output;
	}
	
	/**
	 * Fetches an SQL dump for structure and content of this database.
	 * @return String The SQL statements, creating structure and importing content.
	 */
	public function fetchDump() {
		$command = $this->_renderDumpSqlCommand();
		return $this->shellExec($command);
	}

	/**
	 * @return Resource $sshSession A session handler, as returned by ssh2_connect()
	 */
	protected function _openSshSession() {
		$session = ssh2_connect($this->getHost(), 22, array('hostkey' => 'ssh-dss'));
		ssh2_auth_agent($session, $this->getUser());

		return $session;
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