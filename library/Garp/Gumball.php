<?php
/**
 * Garp_Gumball
 * Represents a packaged Garp installation, including data and source files.
 *
 * Unpack using `tar -xUf gumball.zip`
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
	/**
 	 * @var Zend_Config
 	 */
	protected $_gumballConfig;

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

		// Create config file which can be read @ restore time
		$this->addSettingsFile();

		// Zip target folder
		$this->createZipArchive();

		// Remove target folder
		$this->_removeTargetDirectory();
	}

	/**
 	 * Restore a gumball. Assumption: this is run after a gumball is uploaded and extracted to the
 	 * webroot.
 	 */
	public function restore() {
		// if database, import database
		if ($this->_hasDatabaseDump()) {
			$this->restoreDatabase();
		}

		// set permissions on folders
		$this->setWritePermissions();

		// disable under construction
		Garp_Application::setUnderConstruction(false);
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

	// For now only contains the database source environment, but can be used to add more
	// configuration that's needed @ restore time.
	public function addSettingsFile() {
		$config = new Zend_Config(array(), true);
		$config->gumball = array();
		$config->gumball->sourceDbEnvironment = $this->_dbEnv;

		$writer = new Zend_Config_Writer_Ini(
			array(
				'config' => $config,
				'filename' => $this->_getTargetDirectoryPath() . '/application/configs/gumball.ini'
			)
		);
		$writer->setRenderWithoutSections();
		$writer->write();
	}

	public function createZipArchive() {
		$zipPath = $this->_getGumballDirectory() . '/' . $this->getName() . '.zip';
		$targetDir = $this->_getTargetDirectoryPath();
		return `tar -C {$targetDir} -cf {$zipPath} .`;
	}

	public function createDataDump() {
		if (!$this->_useDatabase) {
			return true;
		}
		$mediator = new Garp_Content_Db_Mediator($this->_dbEnv, $this->_dbEnv);
		$dbServer = $mediator->getSource();

		$dump = $dbServer->fetchDump();
		// @todo Change `from database` to `to database`
		// Of doen we dat @ restore time?
		//$dump = $this->_removeDefinerCalls($dump, $dbServer);
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

	public function restoreDatabase() {
		// @todo Assuming CLI environment, better to use some kind of abstract output writer
		Garp_Cli::lineOut('Restoring database...');
		$sourceEnv = $this->_getGumballConfig('sourceDbEnvironment');
		$mediator = new Garp_Content_Db_Mediator($sourceEnv, APPLICATION_ENV);
		$target = $mediator->getTarget();
		$dump = file_get_contents(
			APPLICATION_PATH . '/data/sql/' . basename($this->_getDataDumpLocation()));
		$target->restore($dump);
	}

	public function setWritePermissions() {
		$root = APPLICATION_PATH . '/..';
		system("chmod -R g+w $root");
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

	protected function _hasDatabaseDump() {
		return file_exists(APPLICATION_PATH . '/data/sql/' .
			basename($this->_getDataDumpLocation()));
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
      		if (!$file->isLink() && $file->isDir()) {
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

	// Read config values from packaged config file
	protected function _getGumballConfig($configKey) {
		if (!$this->_gumballConfig) {
			$this->_gumballConfig = new Garp_Config_Ini(APPLICATION_PATH . '/configs/gumball.ini');
		}
		return $this->_gumballConfig->gumball->{$configKey};
	}
}
