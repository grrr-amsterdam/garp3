<?php
/**
 * Garp_Gumball
 * Represents a packaged Garp installation, including data and source files.
 *
 * @author       Harmen Janssen | grrr.nl
 * @version      0.1.0
 * @package      Garp
 */
class Garp_Gumball {
	const COPY_SOURCEFILE_CMD = 'cp -R %s %s 2>&1';

	/**
 	 * @var Garp_Semver
 	 */
	protected $_version;
	protected $_dbEnv;
	protected $_useDatabase = true;
	protected $_gumballDirectory;

	// Paths that are not copied into the gumball
	protected $_ignoredPaths = array('.DS_Store', 'node_modules', 'bower_components', 'gumballs');

	/** Class constructor */
	public function __construct(Garp_Semver $version, array $options = array()) {
		$this->_version = $version;
		$this->_useDatabase = array_get($options, 'useDatabase', false);
		$this->_dbEnv = array_get($options, 'databaseSourceEnvironment');

		if (!isset($options['gumballDirectory'])) {
			// default to /gumballs
			$options['gumballDirectory'] = APPLICATION_PATH . '/../gumballs';
		}
		$this->_gumballDirectory = $options['gumballDirectory'];
	}

	/** Kick off the build */
	public function make() {
		if (!$this->createTargetDirectory()) {
			throw new Garp_Gumball_Exception_CannotWriteTargetDirectory();
		}

		if (!$this->copySourceFilesToTargetDirectory()) {
			throw new Garp_Gumball_Exception_CannotCopySourceFiles();
		}

		// Create database dump of given environment
		if (!$this->createDataDump()) {
			throw new Garp_Gumball_Exception_DatadumpFailed();
		}

		// Create Under Construction index.html in public that's used when unpacking the gumball
		$this->addUnderConstructionLock();

		// Create the unpack script, move to target folder

		// Zip target folder
		$this->createZipArchive();

		// Remove target folder
		$this->_removeTargetDirectory();
	}

	/** Check wether gumball exists */
	public function exists() {
		return file_exists($this->_getGumballDirectory() . '/' . $this->getName() . '.zip');
	}

	public function getName() {
		return Garp_Util_String::toDashed(Zend_Registry::get('config')->app->name) . '-' .
			$this->_version;
	}

	public function createTargetDirectory() {
		if (file_exists($this->_getTargetDirectoryPath())) {
			$this->_removeTargetDirectory();
		}
		return mkdir($this->_getTargetDirectoryPath());
	}

	public function copySourceFilesToTargetDirectory() {
		$this->_createMissingDirectories();
		$dirIterator = new DirectoryIterator(APPLICATION_PATH . '/..');
		foreach ($dirIterator as $finfo) {
			if (!$this->_copySourceFileToTargetDirectory($finfo)) {
				return false;
			}
		}
		return true;
	}

	public function createZipArchive() {
		$zipPath = $this->_getGumballDirectory() . '/' . $this->getName() . '.zip';
		$zip = new Garp_File_ZipArchive();
		if (true !== $zip->open($zipPath, ZipArchive::OVERWRITE)) {
			throw new Garp_Gumball_Exception_CannotCreateZip();
		}
		$zip->addDirectory($this->_getTargetDirectoryPath());
		return $zip->close();
	}

	public function createDataDump() {
		if (!$this->_useDatabase) {
			return true;
		}
		$dbServer = new Golem_Content_Db_Server_Remote($this->_dbEnv, $this->_dbEnv);
		$dump = $dbServer->fetchDump();
		// @todo Change `from database` to `to database`
		// Of doen we dat @ restore time?
		$dump = $this->_removeDefinerCalls($dump, $dbServer);
		return file_put_contents($this->_getDataDumpLocation(), $dump);
	}

	/**
 	 * Make sure the gumball will extract in under construction mode
 	 */
	public function addUnderConstructionLock() {
		touch($this->_getTargetDirectoryPath() . '/' .
			Garp_Application::UNDERCONSTRUCTION_LOCKFILE);
	}

	public function remove() {
		$zipPath = $this->_getGumballDirectory() . '/' . $this->getName() . '.zip';
		unlink($zipPath);
	}

	protected function _createMissingDirectories() {
		$appData = APPLICATION_PATH . '/data';
		$appDataCache = "{$appData}/cache";
		$paths = array(
			'public/cached', $appDataCache, "$appDataCache/tags",
			"$appDataCache/HTML", "$appDataCache/URI", "$appDataCache/CSS");

		foreach ($paths as $path) {
			if (!file_exists($path)) {
				mkdir($path);
			}
		}
	}

	protected function _getDataDumpLocation() {
		return $this->_getTargetDirectoryPath() . '/application/data/sql/' .
			$this->getName() . '.sql';
	}

	protected function _removeDefinerCalls($dump, Golem_Content_Db_Server_Abstract $dbServer) {
		// Note: taken from Golem_Content_Db_Server_Abstract::_removeDefinerCalls
		$dbConfig = $dbServer->getDbConfigParams();
		$dbUser = $dbConfig->username;
		$dbHost = $dbConfig->host;

		// Replace DEFINER statement with the actual host...
		$definerStringWithHost = sprintf(Golem_Content_Db_Server_Abstract::SQL_DEFINER_STATEMENT,
			$dbUser, $dbHost);
		$dump = str_replace($definerStringWithHost, '', $dump);

		// ...but also replace DEFINER statement where host is a wildcard character
		$definerStringWithWildcard = sprintf(Golem_Content_Db_Server_Abstract::SQL_DEFINER_STATEMENT,
			$dbUser, '%');
		$dump = str_replace($definerStringWithWildcard, '', $dump);
		return $dump;
	}

	protected function _copySourceFileToTargetDirectory(SplFileInfo $finfo) {
		if ($this->_fileShouldBeIgnored($finfo)) {
			return true;
		}
		$fromPath = $finfo->getPath() . '/' . $finfo->getFilename();
		$toPath = rtrim($this->_getTargetDirectoryPath(), '/') . '/';
		exec(sprintf(self::COPY_SOURCEFILE_CMD, $fromPath, $toPath), $output, $status);
		if ($status !== 0) {
			exit(implode("\n", $output));
		}
		return $status === 0;
	}

	protected function _fileShouldBeIgnored(DirectoryIterator $finfo) {
		// ignore dot files...
		return $finfo->isDot() ||
			// ...git files...
			strpos($finfo->getFilename(), '.git') === 0 ||
			// ...and known ignored paths.
			in_array(basename($finfo->getFilename()), $this->_ignoredPaths);
	}

	protected function _getTargetDirectoryPath() {
		return $this->_getGumballDirectory() . '/' .  $this->getName();
	}

	protected function _removeTargetDirectory() {
		if (!$this->_getTargetDirectoryPath()) {
			return false;
		}
		// Recursively remove target dir and its contents
		$iterator = new RecursiveDirectoryIterator($this->_getTargetDirectoryPath());
    	foreach (new RecursiveIteratorIterator($iterator,
			RecursiveIteratorIterator::CHILD_FIRST) as $file) {
			if (in_array($file->getFilename(), array('.', '..'))) {
				continue;
			}
      		if ($file->isDir()) {
         		rmdir($file->getPathname());
      		} else {
         		unlink($file->getPathname());
      		}
    	}
    	rmdir($this->_getTargetDirectoryPath());
	}

	protected function _getGumballDirectory() {
		return $this->_gumballDirectory;
	}

}
