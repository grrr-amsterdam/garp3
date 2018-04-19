<?php
/**
 * Garp_Shell_Command_CreateDir_Test
 *
 * @package Tests
 * @author David Spreekmeester <david@grrr.nl>
 * @group Shell
 */
class Garp_Shell_Command_CreateDir_Test extends Garp_Test_PHPUnit_TestCase {
    const BOGUS_DIR_NAME = 'foobar-dir';

    public function test_Should_be_able_to_create_dir() {
        $createDirCommand = new Garp_Shell_Command_CreateDir(self::BOGUS_DIR_NAME);
        $createDirCommand->executeLocally();

        $dirExists = file_exists(self::BOGUS_DIR_NAME);

        $this->assertTrue($dirExists);

        rmdir(self::BOGUS_DIR_NAME);
    }

}
