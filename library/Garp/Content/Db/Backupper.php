<?php
/**
 * ------DEPRECATED---------
 * Garp_Content_Db_Backupper
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Db_Backupper {	

	const COMMAND_DUMP = "mysqldump -u'%s' -p'%s' --add-drop-table --host='%s' --databases %s";
	
	const COMMAND_CREATE_BACKUP_PATH = "mkdir -p -m 770 %s";

	const PATH_CONFIG_APP = '/configs/application.ini';
	
	const PATH_BACKUP_CAPIFIED = '/shared/backup/db';
	
	const PATH_BACKUP_NOT_CAPIFIED = '/data/sql';


	/**
	 * @var String $_targetEnv The id of the target environment
	 */
	protected $_targetEnv;

	/**
	 * @var Array $_deployParams Deployment parameters containing values for 'server', 'user' and 'deploy_to' path.
	 */
	protected $_deployParams;

	/**
	 * @var Zend_Config_Ini $_appConfigParams 	Application configuration parameters (application.ini)
	 *											for this particular target environment.
	 */
	protected $_appConfigParams;

	/**
	 * @var 	Resource  $_sshSession	A session handler, as returned by ssh2_connect()
	 */	
	protected $_sshSession;
		
	/**
	 * @var		Bool	$_isCapified	Current target environment is Capistrano-enabled and should.
	 */
	protected $_isCapified;

	/**
	 * @var		String		Absolute path to write backup files to.
	 */
	protected $_backupPath;



	/**
	 * @param String $targetEnv The id of the target environment
	 */
	public function backup($targetEnv) {
		$this->_initEnvironment($targetEnv);

		$commands = array(
			$this->_renderCreateBackupPath(),
			$this->_renderDumpShellCommand()
		);

		foreach ($commands as $command) {
			$this->_shellExec($command);
		}
	}
	
	public function getBackupPath() {
		if ($this->isCapified()) {
			$backupPath = $this->getRemotePath() . self::PATH_BACKUP_CAPIFIED;
		} else {
			$backupPath = APPLICATION_PATH . self::PATH_BACKUP_NOT_CAPIFIED;
		}
		
		return $backupPath;
	}

	public function isRemote() {
		$targetEnv = $this->getTargetEnv();
		return $targetEnv !== 'development';
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
	 * @return String
	 */
	public function getTargetEnv() {
		return $this->_targetEnv;
	}
	
	/**
	 * @return Zend_Config_Ini
	 */
	public function getAppConfigParams() {
		return $this->_appConfigParams;
	}

	/**
	 * @return Array
	 */
	public function getDeployParams() {
		return $this->_deployParams;
	}
		
	/**
	 * @return Zend_Config_Ini
	 */
	public function getDbConfigParams() {
		$appConfigParams = $this->getAppConfigParams();
		return $appConfigParams->resources->db->params;
	}
	
	
	public function isCapified() {
		return $this->_isCapified;
	}

	/**
	 * @param Array Deployment parameters containing values for 'server', 'user' and 'deploy_to' path.
	 */
	public function setDeployParams(array $deployParams) {
		$this->_deployParams = $deployParams;
	}
		
	public function setAppConfigParams(Zend_Config_Ini $appConfigParams) {
		$this->_appConfigParams = $appConfigParams;
	}

	/**
	 * @param Resource $sshSession A session handler, as returned by ssh2_connect()
	 */
	public function setSshSession($sshSession) {
		$this->_sshSession = $sshSession;
	}
	
	/**
	 * @param String $targetEnv The id of the target environment
	 */
	public function setTargetEnv($targetEnv) {
		$this->_targetEnv = $targetEnv;
	}

	/**
	 * @param String $path
	 */
	public function setBackupPath($path) {
		$this->_backupPath = $path;
	}
	
	/**
	 * @param Bool $isCapified Whether the current target environment is Capistrano-enabled.
	 */
	public function setCapifiedStatus($isCapified) {
		$this->_isCapified = $isCapified;
	}


	protected function _renderDumpSqlCommand() {
		$appConfigParams = $this->getAppConfigParams();

		$dbConfig = $appConfigParams->resources->db->params;
		
		$dumpCommand = sprintf(
			self::COMMAND_DUMP,
			$dbConfig->username,
			$dbConfig->password,
			$dbConfig->host,
			$dbConfig->dbname
		);
		
		return $dumpCommand;
	}
	
	
	protected function _renderDumpShellCommand() {
		$dumpSqlCommand 	= $this->_renderDumpSqlCommand();
		$backupPath 		= $this->getBackupPath();
		$dbConfigParams 	= $this->getDbConfigParams();
		$dbName 			= $dbConfigParams->dbname;
		$targetEnv			= $this->getTargetEnv();
		$date				= date('Y-m-d-Hi');

		$shellCommand		= $dumpSqlCommand . ' > ' . $backupPath . '/'
							. $dbName . '-' . $targetEnv . '-' . $date . '.sql';

		return $shellCommand;
	}
	
	
	protected function _renderCreateBackupPath() {
		$backupPath = $this->getBackupPath();
		return sprintf(self::COMMAND_CREATE_BACKUP_PATH, $backupPath);
	}
	
	
	protected function _initEnvironment($environment) {
		$this->setTargetEnv($environment);
		$this->setAppConfigParams($this->_fetchAppConfigParams());
		$this->setBackupPath($this->getBackupPath());
		
		$this->setCapifiedStatus($this->_fetchCapifiedEnvironmentStatus());
		
		if ($this->isCapified()) {
			$this->setDeployParams($this->_fetchDeployParams());			
			
			if ($this->isRemote()) {
				$sshSession = $this->_openSshSession();
				$this->setSshSession($sshSession);
			}
		}		
	}


	protected function _fetchCapifiedEnvironmentStatus() {
		$deployConfig = new Garp_Deploy_Config();
		$targetEnv = $this->getTargetEnv();
		
		return (
			$deployConfig->isConfigured($targetEnv) &&
			$this->isRemote()
		);
	}
	
	
	/**
	 * @param String $command Shell command
	 */
	protected function _shellExec($command) {
		if ($this->isCapified()) {
			$sshSession = $this->getSshSession();
			if ($stream = ssh2_exec($sshSession, $command)) {
				stream_set_blocking($stream, true);
				$output = stream_get_contents($stream);
			} else return false;
		} else {
			$output = exec($command);
		}
		
		return $output;
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
		$deployParams = $deployConfig->getParams($this->_targetEnv);
		return $deployParams;
	}

	protected function _fetchAppConfigParams() {
		$targetConfig = new Zend_Config_Ini(APPLICATION_PATH . self::PATH_CONFIG_APP, $this->getTargetEnv());
		return $targetConfig;
	}

}