<?php
/**
 * Garp_Shell_Command_RemoveFile_Test
 *
 * @package Tests
 * @author  David Spreekmeester <david@grrr.nl>
 * @group   Shell
 */
class Garp_Shell_Command_RemoveFile_Test extends Garp_Test_PHPUnit_TestCase {
    const BOGUS_FILE_NAME = 'foobar-file';

    public function test_Should_be_able_to_create_dir() {
        file_put_contents(self::BOGUS_FILE_NAME, 'testing');

        $exists = file_exists(self::BOGUS_FILE_NAME);
        $this->assertTrue($exists);

        $removeFileCommand = new Garp_Shell_Command_RemoveFile(self::BOGUS_FILE_NAME);
        $removeFileCommand->executeLocally();

        $exists = file_exists(self::BOGUS_FILE_NAME);

        $this->assertFalse($exists);
    }

}
