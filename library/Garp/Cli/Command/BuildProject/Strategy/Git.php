<?php
/**
 * Garp_Cli_Command_BuildProject_Git
 * Build project using Git
 *
 * @author       $Author: harmen $
 * @modifiedby   $LastChangedBy: harmen $
 * @version      $LastChangedRevision: 6540 $
 * @package      Garp
 * @subpackage   BuildProject
 * @lastmodified $LastChangedDate: 2012-10-10 10:16:37 +0200 (Wed, 10 Oct 2012) $
 */
class Garp_Cli_Command_BuildProject_Strategy_Git implements Garp_Cli_Command_BuildProject_Strategy_Interface {
	/**
	 * Garp3 repository URL
	 * @var String
	 */
	const GARP3_REPO = 'git@flow.grrr.nl:garp3';
	
	
	/**
	 * Zend Framework repository
	 * @var String
	 */
	const ZEND_REPO = 'http://framework.zend.com/svn/framework/standard/trunk/library/Zend';


	/**
 	 * Project name
 	 * @var String
 	 */
	protected $_projectName;


	/**
 	 * Project repository
 	 * @var String
 	 */
	protected $_projectRepository;


	/**
 	 * Project root
 	 * @var String
 	 */
	protected $_projectRoot;


	/**
 	 * Class constructor
 	 * @param String $projectName
 	 * @param String $repository
 	 * @return Void
 	 */
	public function __construct($projectName, $repository = null) {
		$repository = $repository ?: 'git@flow.grrr.nl:'.$projectName;

		$this->_projectName = $projectName;
		$this->_projectRepository = $repository;
		$this->_projectRoot = getcwd().'/'.$projectName;
	}


	/**
 	 * Build all the things
 	 * @return Void
 	 */
	public function build() {
		if (file_exists($this->_projectRoot)) {
			Garp_Cli::errorOut('The project folder already exists. I don\'t know what to do.');
		} else {
			// sanity check: is the repository accessible?
			$checkCommand = 'if ( git ls-remote '.$this->_projectRepository.' &> /dev/null ); then echo \'accessible\'; fi';
			$checkResult  = trim(`$checkCommand`);
			if ('accessible' !== $checkResult) {
				Garp_Cli::errorOut('The repository you\'re trying to checkout either does not exist or you do not have access rights.');
				return false;
			}
			// start by checking out the project repo 
			$this->_checkOutProjectRepository();

			chdir($this->_projectRoot);

			$this->_setupGarp();
			$this->_setupGarpSubmodules();
			$this->_createScaffolding();
			$this->_checkOutZend();
			$this->_createSymlinks();
			$this->_fixPermissions();
			$this->_addFilesToGit();

			Garp_Cli::lineOut('Project created successfully. Thanks for watching.');
		}
	}


	/**
 	 * Check out project repo
 	 */
	protected function _checkOutProjectRepository() {
		Garp_Cli::lineOut(' # Cloning project repository: '.$this->_projectRepository);
		passthru('git clone '.$this->_projectRepository);
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');		
	}


	/**
 	 * Setup Garp submodule
 	 */
	protected function _setupGarp() {
		Garp_Cli::lineOut(' # Setting up Garp submodule');
		passthru('git submodule add '.self::GARP3_REPO.' garp');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}


	/**
 	 * Setup submodules inside Garp (such as the Facebook SDK).
 	 */
	protected function _setupGarpSubmodules() {
		Garp_Cli::lineOut(' # Setting up submodules in Garp');
		chdir('garp');
		passthru('git submodule init');
		passthru('git submodule update');
		chdir($this->_projectRoot);
		Garp_Cli::lineOut('Done.');		
		Garp_Cli::lineOut('');
	}


	/**
 	 * Create project scaffolding
 	 */
	protected function _createScaffolding() {
		Garp_Cli::lineOut(' # Creating scaffolding');
		// copy scaffold files
		passthru('cp -R garp/scripts/scaffold/application application');
		passthru('cp -R garp/scripts/scaffold/docs docs');
		passthru('cp -R garp/scripts/scaffold/library library');
		passthru('cp -R garp/scripts/scaffold/public public');
		passthru('cp -R garp/scripts/scaffold/tests tests');
		passthru('cp -R garp/scripts/scaffold/.htaccess .htaccess');
		passthru('cp -R garp/scripts/scaffold/__MANIFEST.md __MANIFEST.md');

		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}


	/**
 	 * Checkout Zend library
 	 */
	protected function _checkOutZend() {
		Garp_Cli::lineOut(' # Checking out the Zend library');
		passthru('svn export '.self::ZEND_REPO.' library/Zend');
		passthru('git add library/Zend');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}


	/**
 	 * Create symlinks
 	 */
	protected function _createSymlinks() {
		Garp_Cli::lineOut(' # Creating symlinks');
		passthru('ln -s ../garp/library/Garp library/Garp');
		passthru('git add library/Garp');
		passthru('ln -s ../../garp/public/css public/css/garp');
		passthru('git add public/css/garp');
		passthru('ln -s ../../garp/public/js public/js/garp');
		passthru('git add public/js/garp');
		passthru('ln -s ../../../garp/public/images public/media/images/garp');
		passthru('git add public/media/images/garp');
		passthru('ln -s ../garp/library/Garp/3rdParty/PHPExcel/Classes/PHPExcel library/PHPExcel');
		passthru('git add library/PHPExcel');
		passthru('ln -s ../../../../shared/uploads public/uploads/shared');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}


	/**
 	 * Set write permissions on certain folders
 	 */
	protected function _fixPermissions() {
		Garp_Cli::lineOut(' # Setting permissions');
		passthru('chmod -R 777 application/data/cache');
		passthru('chmod -R 777 application/data/logs');
		passthru('chmod -R 777 public/uploads');
		// passthru('chmod -R 777 garp/library/Garp/3rdParty/dompdf/lib/fonts');

		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}


	/**
 	 * Add files to git, and add some files to .gitignore
 	 */
	protected function _addFilesToGit() {
		// Add files to staging area
		passthru('git add application');
		passthru('git add docs');
		passthru('git add garp');
		passthru('git add library');
		passthru('git add public');
		passthru('git add tests');
		passthru('git add .htaccess');
		passthru('git add .gitmodules');		
		passthru('git add __MANIFEST.md');

		$ignoreThis  = "application/data/cache/pluginLoaderCache.php\n";
		$ignoreThis .= "application/data/cache/URI/*\n";
		$ignoreThis .= "application/data/cache/HTML/*\n";
		$ignoreThis .= "application/data/cache/CSS/*\n";
		$ignoreThis .= "application/data/cache/tags/*\n";
		$ignoreThis .= "public/cached/**/*\n";
		$ignoreThis .= "public/css/.sass-cache\n";
		$ignoreThis .= "public/uploads/private/**/*\n";
		$ignoreThis .= "public/uploads/shared/**/*\n";
		$ignoreThis .= ".DS_Store\n";
		$ignoreThis .= ".project\n";
		file_put_contents('.gitignore', $ignoreThis);
		passthru('git add .gitignore');
	}
}
