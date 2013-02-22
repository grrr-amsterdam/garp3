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

	const SSH_PRIVATE_KEY = '/Users/%s/.ssh/id_dsa';
	const SSH_PUBLIC_KEY = '/Users/%s/.ssh/id_dsa.pub';
	
	
	public function __construct($environment) {
		parent::__construct($environment);
		$this->_checkRequirements();		
	}


	/**
	 * @return Garp_Content_Upload_FileList
	 */
	public function fetchFileList() {
		$fileList = new Garp_Content_Upload_FileList();
		
		$deployConfig = new Garp_Deploy_Config();
		$deployParams = $deployConfig->getParams($this->getEnvironment());

		$session = $this->_openSshSession($deployParams['server']);
		$configuredPaths = $this->_getConfiguredPaths();

		foreach ($configuredPaths as $uploadTypePath) {
			$lsCommand = "ls -og {$deployParams['deploy_to']}/current/public{$uploadTypePath}";

			$stream = ssh2_exec($session, $lsCommand);
			stream_set_blocking($stream, true);
			$dirListing = stream_get_contents($stream) . "\n";
			fclose($stream);
			
			$matches = array();
			$pattern = '/(?P<permissions>[rwx\-+@]+)\s+\d+\s+(?P<filesize>\d+)\s+(?P<lastmodified>\w{3}\s+\d+\s+\d+:?\d+)\s+(?P<filename>[^ ]+)\n/';
			preg_match_all($pattern, $dirListing, $matches);
			
			foreach ($matches['permissions'] as $index => $permission) {
				if ($permission[0] !== 'd') {
					//	this is a file, no directory
					$fileList->addEntry(
						$uploadTypePath . '/' . $matches['filename'][$index],
						strtotime($matches['lastmodified'][$index])
					);
				}
			}
		}
		
		return $fileList;
	}
	
	
	protected function _openSshSession($host) {
		$session = ssh2_connect($host, 22, array('hostkey' => 'ssh-dss'));

		$localUser = exec('whoami');
		ssh2_auth_pubkey_file(
			$session,
			$localUser,
			sprintf(self::SSH_PUBLIC_KEY, $localUser),
			sprintf(self::SSH_PRIVATE_KEY, $localUser)
		);

		return $session;
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
	}
}