<?php
/**
 * Garp_Cli_Command_BuildProject
 * Builds a new Garp project
 * @author Harmen Janssen | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @subpackage Cli
 * @lastmodified $Date: $
 */
class Garp_Cli_Command_BuildProject extends Garp_Cli_Command {
	/**
	 * Garp3 repository URL
	 * @var String
	 */
	const GARP3_REPO = 'https://svn.grrr.nl/garp3/code/branches/3_5/garp';
	
	
	/**
	 * Zend Framework repository
	 * @var String
	 */
	const ZEND_REPO = 'http://framework.zend.com/svn/framework/standard/trunk/library/Zend';
	
	
	/**
	 * Project root
	 * @var String
	 */
	protected $_projectRoot;
	
	
	/**
	 * Central start method
	 * @param Array $args Various options. Must contain;
	 * @return Void
	 */
	public function main(array $args = array()) {
		if (empty($args[1])) {
			Garp_Cli::errorOut('Insufficient arguments.');
			$this->help();
		} elseif (strtolower($args[1]) === 'help') {
			$this->help();
			return;
		} else {
			$projectName = $args[1];
			$projectRepo = isset($args[2]) ? $args[2] : 'https://svn.grrr.nl/'.$projectName.'/code/trunk';
			
			$this->_projectRoot = getcwd().'/'.$projectName;
			
			if (file_exists($this->_projectRoot)) {
				Garp_Cli::errorOut('The project folder already exists. I don\'t know what to do.');
			} else {
				$this->_checkOutProjectRepository($projectRepo);
				
				// change to new directory
				chdir($projectName);
				
				if (file_exists('garp')) {
					Garp_Cli::lineOut('Garp directory already in place. Apparently I\'m done.');
				} else {
					$this->_checkOutGarp();
					$this->_createScaffolding();
					$this->_checkOutZend();
					$this->_createSymlinks();
					$this->_fixPermissions();
					$this->_addFilesToSvn();

					$this->_checkCopiedZendCode();
					
					Garp_Cli::lineOut('Project created successfully. Thanks for watching.');
				}
			}
		}
	}
	
	
	/**
	 * Check out the SVN repository of the project
	 * @param String $repo
	 * @return Void
	 */
	protected function _checkOutProjectRepository($repo) {
		Garp_Cli::lineOut('Checking out project repository: '.$repo.'...');
		passthru('svn co '.$repo.' '.$this->_projectRoot);
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}
	
	
	/**
	 * Set the Garp repository as an external
	 * @return Void
	 */
	protected function _checkOutGarp() {
		Garp_Cli::lineOut('Checking out Garp3 repository: '.self::GARP3_REPO.'...');
		passthru('svn ps svn:externals \'garp '.self::GARP3_REPO.'\' .');
		passthru('svn up');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}
	
	
	/**
 	 * Create the basic directory structure for Garp projects.
 	 * This is taken from the scaffold folder inside garp/scripts.
 	 * @return Void
 	 */
	protected function _createScaffolding() {
		Garp_Cli::lineOut('Creating scaffolding');
		passthru('svn export garp/scripts/scaffold/application application');
		passthru('svn export garp/scripts/scaffold/docs docs');
		passthru('svn export garp/scripts/scaffold/library library');
		passthru('svn export garp/scripts/scaffold/public public');
		passthru('svn export garp/scripts/scaffold/tests tests');
		passthru('svn export garp/scripts/scaffold/.htaccess .htaccess');
		passthru('svn export garp/scripts/scaffold/__MANIFEST.md __MANIFEST.md');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}


	/**
	 * Export Zend framework repository
	 * @return Void
	 */
	protected function _checkOutZend() {
		Garp_Cli::lineOut('Checking out Zend...');
		passthru('svn export '.self::ZEND_REPO.' library/Zend');
		passthru('svn add library/Zend');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}

	
	/**
	 * Create symlinks
	 * @return Void
	 */
	protected function _createSymlinks() {
		Garp_Cli::lineOut('Creating symlinks...');
		passthru('ln -s ../garp/library/Garp library/Garp');
		passthru('svn add library/Garp');
		passthru('ln -s ../../garp/public/css public/css/garp');
		passthru('svn add public/css/garp');
		passthru('ln -s ../../garp/public/js public/js/garp');
		passthru('svn add public/js/garp');
		passthru('ln -s ../../../garp/public/images public/media/images/garp');
		passthru('svn add public/media/images/garp');
		passthru('ln -s ../garp/library/Garp/3rdParty/PHPExcel/Classes/PHPExcel library/PHPExcel');
		passthru('svn add library/PHPExcel');
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}
	
	
	/**
	 * Fix permissions
	 * @return Void
	 */
	protected function _fixPermissions() {
		Garp_Cli::lineOut('Fixing permissions...');
		// note; passthru() is used instead of php's chmod() because the shell chmod command can work recursively 
		passthru('chmod -R 777 application/data/cache');
		passthru('chmod -R 777 application/data/logs');
		passthru('chmod -R 777 public/uploads');
		passthru('chmod -R 777 garp/library/Garp/3rdParty/dompdf/lib/fonts');
		passthru('chmod -ugo+x garp/scripts/garp');
		
		Garp_Cli::lineOut('Done.');
		Garp_Cli::lineOut('');
	}
	
	
	/**
 	 * Miscellaneous maintenance functions
 	 * @return Void
 	 */
	protected function _addFilesToSvn() {
		passthru('svn add application');
		passthru('svn add docs');
		passthru('svn add library');
		passthru('svn add public');
		passthru('svn add tests');
		passthru('svn add .htaccess');
		passthru('svn add __MANIFEST.md');
		passthru('svn ps svn:ignore \'pluginLoaderCache.php\' application/data/cache');
		passthru('svn ps svn:ignore \'*\' application/data/cache/URI');
		passthru('svn ps svn:ignore \'*\' application/data/cache/HTML');
		passthru('svn ps svn:ignore \'*\' application/data/cache/CSS');
		passthru('svn ps svn:ignore \'*\' application/data/cache/tags');
		passthru('svn ps svn:ignore \'*\' public/cached');
		passthru('svn ps svn:ignore \'.sass-cache\' public/css');
		passthru('svn ps svn:ignore \'*\' public/uploads/sandbox');
	}
	

	/**
	 * There is some copied code in Garp_Db_Table_Row from Zend_Db_Table_Row_Abstract 
	 * this is not really a problem, but let's check here if that code is still in 
	 * Zend_Db_Table_Row_Abstract. If not, show a notification so the altered code 
	 * might be copied back into Garp_Db_Table_Row.
	 * @return Void
	 */
	protected function _checkCopiedZendCode() {
		$lines = array(
			'for ($i = 0; $i < count($callerMap[Zend_Db_Table_Abstract::COLUMNS]); ++$i) {',
			'$callerColumnName = $db->foldCase($callerMap[Zend_Db_Table_Abstract::REF_COLUMNS][$i]);',
			'$value = $this->_data[$callerColumnName];',
			'$interColumnName = $interDb->foldCase($callerMap[Zend_Db_Table_Abstract::COLUMNS][$i]);',
			'$interCol = $interDb->quoteIdentifier("i.$interColumnName", true);',
			'$interInfo = $intersectionTable->info();',
			'$type = $interInfo[Zend_Db_Table_Abstract::METADATA][$interColumnName][\'DATA_TYPE\'];',
			'$select->where($interDb->quoteInto("$interCol = ?", $value, $type));',
		);
		$currentZendContent = file_get_contents('library/Zend/Db/Table/Row/Abstract.php');
		foreach ($lines as $line) {
			if (strpos($currentZendContent, $line) === false) {
				Garp_Cli::errorOut('Warning: library/Zend/Db/Table/Row/Abstract.php has been updated. The rules copied to Garp_Db_Table_Row may need to be corrected.');
				break;
			}
		}
	}


	/**
 	 * Help
 	 */
	public function help() {
		Garp_Cli::lineOut('Usage:');
		Garp_Cli::lineOut('  g BuildProject <projectname> <svn repository>');
		Garp_Cli::lineOut('');
	}
}
