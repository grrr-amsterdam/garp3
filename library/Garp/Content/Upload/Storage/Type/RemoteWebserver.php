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

		foreach ($configuredPaths as $type => $relDir) {
			$fileListByType = $this->fetchFileListByType($type, $relDir);
			$fileList->addEntries($fileListByType);
		}

		return $fileList;
	}

	/**
	 * @return Garp_Content_Upload_FileList
	 */	
	public function fetchFileListByType($type, $relDir) {
		$fileList = new Garp_Content_Upload_FileList();		
		$baseDir = $this->_getBaseDir();
		$lsCommand = "ls -ogl {$baseDir}{$relDir}";
		
		$session = $this->getSshSession();
		$stream = ssh2_exec($session, $lsCommand);
		$dirListing = $this->_fetchAndCloseStream($stream) . "\n";
			
		$matches = array();
		$pattern = '/(?P<permissions>[drwx\-+@]+)\s+\d+\s+(?P<filesize>\d+)\s+(?P<lastmodified>\w{3}\s+\d+\s+\d+:?\d+)\s+(?P<filename>[^ \n]+)\n+/';
		preg_match_all($pattern, $dirListing, $matches);
			
		foreach ($matches['permissions'] as $index => $permission) {
			if ($permission[0] !== 'd') {
				//	this is a file, no directory
				$fileNode = new Garp_Content_Upload_FileNode(
					$matches['filename'][$index],
					$type
				);

				$fileList->addEntry($fileNode);
			}
		}
		
		return $fileList;
	}
	
	/**
	 * Calculate the eTag of a file.
	 * @param String $filename 	Filename
	 * @param String $type		File type, i.e. 'document' or 'image'
	 * @return String 			Content hash (md5 sum of the content)
	 */
	public function fetchEtag($filename, $type) {
		$baseDir 	= $this->_getBaseDir();
		$absPath 	= $baseDir . $this->_getRelPath($filename, $type);

		$session 	= $this->getSshSession();
		
		$md5command = "cat {$absPath} | md5sum";
		$stream 	= ssh2_exec($session, $md5command);
		$md5output 	= $this->_fetchAndCloseStream($stream);

		if ($md5output) {
			$baddies = array(' ', '-', "\n");
			$md5output = str_replace($baddies, '', $md5output);
			return $md5output;
		} else throw new Exception("Could not fetch md5 sum of {$path}.");
	}


	/**
	 * Fetches the contents of the given file.
	 * @param String $filename 	Filename
	 * @param String $type		File type, i.e. 'document' or 'image'
	 * @return String			Content of the file. Throws an exception if file could not be read.
	 */
	public function fetchData($filename, $type) {
		$absPath 	= $this->_getAbsPath($filename, $type);
		$sftpStream = $this->getSftpStream($absPath, 'rb');
		
		$content 	= $this->_fetchAndCloseStream($sftpStream);
		
		if ($content !== false) {
			return $content;
		} else throw new Exception("Could not read {$absPath} on " . $this->getEnvironment());
	}
	
	
	/**
	 * Stores given data in the file, overwriting the existing bytes if necessary.
	 * @param String $filename 	Filename
	 * @param String $type		File type, i.e. 'document' or 'image'
	 * @param String $data		File data to be stored.
	 * @return Boolean			Success of storage.
	 */
	public function store($filename, $type, $data) {
		$remoteAbsPath 	= $this->_getAbsPath($filename, $type);
		$sftpStream 	= $this->getSftpStream($remoteAbsPath);

	    if (@fwrite($sftpStream, $data) === false) {
	        throw new Exception("Could not store {$remoteAbsPath} by SFTP on " . $this->getEnvironment());
	    }

	    fclose($sftpStream);
		return true;
	}
	
	public function setDeployParams(array $deployParams) {
		$this->_deployParams = $deployParams;
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
	
	public function getSshSession() {
		return $this->_sshSession;
	}


	public function getSftpSession() {
		return $this->_sftpSession;
	}

	public function getSftpStream($absPath, $mode = 'wb') {
		$sftpSession 	= $this->getSftpSession();
		$sftpStream 	= @fopen('ssh2.sftp://' . $sftpSession . $absPath, $mode);
		
	    if (!$sftpStream) {
	        throw new Exception("Could not open remote location: $absPath");
	    }
		
		return $sftpStream;
	}

	protected function _openSshSession($host) {
		$session = ssh2_connect($host, 22, array('hostkey' => 'ssh-dss'));
		ssh2_auth_agent($session, $this->getUser());

		return $session;
	}
	
	protected function _fetchAndCloseStream($stream) {
		stream_set_blocking($stream, true);
		$content = stream_get_contents($stream);
		fclose($stream);
		return $content;
	}

	/**
	 * @param 	String $filename 	Filename
	 * @param 	String $type		File type, i.e. 'document' or 'image'
	 * @return 	String				The absolute path to this file for use on the local file system
	 */
	protected function _getAbsPath($filename, $type) {
		$baseDir 		= $this->_getBaseDir();
		$relPath 		= $this->_getRelPath($filename, $type);
		return $baseDir . $relPath;
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