<?php
/**
 * Garp_Content_Upload_Storage_Type_RemoteWebserver
 * 
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Content
 * @lastmodified $Date: $
 */
class Garp_Content_Upload_Storage_Type_RemoteWebserver extends Garp_Content_Upload_Storage_Type_Abstract {	
	/**
	 * Array $_deployParams Associative array containing 'server', 'deploy_to' and 'user'
	 */
	protected $_deployParams;
	
	protected $_sshSession;
	
	protected $_sftpSession;
	
	
	public function __construct($environment) {
		parent::__construct($environment);
		$this->_checkRequirements();
		$this->setDeployParams($this->getDeployParams());
		$this->setSshSession($this->_openSshSession($this->getServer()));
		$this->setSftpSession($this->getSshSession());
	}


	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList() {
		$fileList = new Garp_Content_Upload_FileList();		
		$configuredPaths = $this->_getConfiguredPaths();
		$session = $this->getSshSession();
		$baseDir = $this->_getBaseDir();

		foreach ($configuredPaths as $uploadTypePath) {
			$lsCommand = "ls -og {$baseDir}{$uploadTypePath}";

			$stream = ssh2_exec($session, $lsCommand);
			stream_set_blocking($stream, true);
			$dirListing = stream_get_contents($stream) . "\n";
			fclose($stream);
			
			$matches = array();
			$pattern = '/(?P<permissions>[rwx\-+@]+)\s+\d+\s+(?P<filesize>\d+)\s+(?P<lastmodified>\w{3}\s+\d+\s+\d+:?\d+)\s+(?P<filename>[^ \n]+)\n+/';
			preg_match_all($pattern, $dirListing, $matches);
			
			foreach ($matches['permissions'] as $index => $permission) {
				if ($permission[0] !== 'd') {
					//	this is a file, no directory
					$fileList->addEntry(
						$uploadTypePath . '/' . $matches['filename'][$index]
					);
				}
			}
		}
		
		return $fileList;
	}
	
	
	/**
	 * Calculate the eTag of a file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String 		Content hash (md5 sum of the content)
	 */
	public function fetchEtag($path) {
		$baseDir = $this->_getBaseDir();
		$absPath = $baseDir . $path;
		$session = $this->getSshSession();
		
		$md5command = "cat {$absPath} | md5sum";
		$stream = ssh2_exec($session, $md5command);
		stream_set_blocking($stream, true);
		$md5output = stream_get_contents($stream);
		fclose($stream);

		if ($md5output) {
			$baddies = array(' ', '-', "\n");
			$md5output = str_replace($baddies, '', $md5output);
			return $md5output;
		} else throw new Exception("Could not fetch md5 sum of {$path}.");
	}


	/**
	 * Fetches the contents of the given file.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @return String		Content of the file. Throws an exception if file could not be read.
	 */
	public function fetchData($path) {
		$ini = $this->_getIni();
		$cdnDomain = $ini->cdn->domain;
		$url = 'http://' . $cdnDomain . $path;
		
		$content = file_get_contents($url);
		if ($content !== false) {
			return $content;
		} else throw new Exception("Could not read {$url} on " . $this->getEnvironment());
	}
	
	
	/**
	 * Stores given data in the file, overwriting the existing bytes if necessary.
	 * @param String $path 	Relative path to the file, starting with a slash.
	 * @param String $data	File data to be stored.
	 * @return Boolean		Success of storage.
	 */
	public function store($path, $data) {
		$sftpSession = $this->getSftpSession();
		$remoteAbsPath = $this->_getBaseDir() . $path;

		$sftpStream = @fopen('ssh2.sftp://' . $sftpSession . $remoteAbsPath, 'wb');

	    if (!$sftpStream) {
	        throw new Exception("Could not open remote file: $remoteAbsPath");
	    }

	    if (@fwrite($sftpStream, $data) === false) {
	        throw new Exception("Could not store {$remoteAbsPath} by SFTP on " . $this->getEnvironment());
	    }

	    fclose($sftpStream);
		return true;
	}
	
	
	/**
	 * @param Resource $sshSession A session handler, as returned by ssh2_connect().
	 */
	public function setSshSession($sshSession) {
		$this->_sshSession = $sshSession;
	}


	/**
	 * @param Resource $sshSession A session handler, as returned by ssh2_connect().
	 */
	public function setSftpSession($sshSession) {
		$this->_sftpSession = ssh2_sftp($sshSession);
	}


	/**
	 * Fetches the deploy parameters for the environment which this storage instance runs on.
	 */
	public function getDeployParams() {
		$deployConfig = new Garp_Deploy_Config();
		$deployParams = $deployConfig->getParams($this->getEnvironment());
		return $deployParams;
	}
	
	
	public function getServer() {
		return $this->_deployParams['server'];
	}

	
	public function getUser() {
		return $this->_deployParams['user'];
	}
	
	
	public function setDeployParams(array $deployParams) {
		$this->_deployParams = $deployParams;
	}
	
	
	public function getSshSession() {
		return $this->_sshSession;
	}


	public function getSftpSession() {
		return $this->_sftpSession;
	}


	protected function _openSshSession($host) {
		$session = ssh2_connect($host, 22, array('hostkey' => 'ssh-dss'));
		ssh2_auth_agent($session, $this->getUser());

		return $session;
	}
	
	
	/**
	 * @return String Absolute path on the server, exluding trailing slash.
	 */
	protected function _getBaseDir() {
		$deployParams = $this->getDeployParams();
		$baseDir = $deployParams['deploy_to'] . '/current/public';
		return $baseDir;
	}
	
	
	protected function _checkRequirements() {
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