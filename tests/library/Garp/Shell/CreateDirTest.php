<?php
/**
 * Garp_Shell_Command_CreateDir_Test
 * @author David Spreekmeester | grrr.nl
 * @modifiedby $LastChangedBy: $
 * @version $Revision: $
 * @package Garp
 * @group Shell
 * @lastmodified $Date: $
 */
class Garp_Shell_Command_CreateDir_Test extends PHPUnit_Framework_TestCase {
	const BOGUS_DIR_NAME = 'foobar-dir';

	public function test_Should_be_able_to_create_dir() {
		$createDirCommand = new Garp_Shell_Command_CreateDir(self::BOGUS_DIR_NAME);
		$createDirCommand->executeLocally();
		
		$dirExists = file_exists(self::BOGUS_DIR_NAME);
		
		$this->assertTrue($dirExists);
		
		rmdir(self::BOGUS_DIR_NAME);
	}
	
}