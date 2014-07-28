<?php

class Garp_Shell_RemoteSession {
	/**
	 * @var String $_host
	 */
	protected $_host;

	/**
	 * @var String $_user
	 */
	protected $_user;
	
	/**
	 * @var Resource $_session
	 */
	protected $_sshSession;


	public function __construct($host, $user) {
		$this->_checkPlatformRequirements();

		$this->setHost($host);
		$this->setUser($user);
		$this->setSshSession($this->_createSshSession());
	}
	
	/**
	 * @return String
	 */
	public function getHost() {
		return $this->_host;
	}
	
	/**
	 * @param String $host
	 */
	public function setHost($host) {
		$this->_host = $host;
	}
	
	/**
	 * @return String
	 */
	public function getUser() {
		return $this->_user;
	}
	
	/**
	 * @param String $user
	 */
	public function setUser($user) {
		$this->_user = $user;
	}
		
	/**
	 * @return Resource SSH session
	 */
	public function getSshSession() {
		return $this->_sshSession;
	}
	
	/**
	 * @param Resource $sshSession
	 */
	public function setSshSession($sshSession) {
		$this->_sshSession = $sshSession;
	}
	
	protected function _createSshSession() {
		$host = $this->getHost();
		$user = $this->getUser();

		$sshSession = ssh2_connect($host, 22, array('hostkey' => 'ssh-dss'));

		if ($sshSession) {
			if (ssh2_auth_agent($sshSession, $user)) {
				return $sshSession;
			} else throw new Exception("Could not authenticate a session to {$host} using the SSH agent.");
		} else throw new Exception("Could not connect to {$host}.");
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