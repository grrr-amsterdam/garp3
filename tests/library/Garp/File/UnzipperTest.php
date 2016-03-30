<?php
/**
 * @group Zip
 */
class Garp_File_UnzipperTest extends Garp_Test_PHPUnit_TestCase {
	const REGULAR_FILE = '/../tests/files/regular_file.txt';
	const ZIPPED_FILE = '/../tests/files/zipped_file.txt.gz';
	const DOUBLE_ZIPPED_FILE = '/../tests/files/double_zipped_file.txt.gz';

	const ZIPPED_CONTENT = 'Hello I\'m zipped';
	const DOUBLE_ZIPPED_CONTENT = 'Hello I\'m double zipped';

	public function testShouldReadRegularFile() {
		$regularContents = file_get_contents(GARP_APPLICATION_PATH . self::REGULAR_FILE);
		$unzipper = new Garp_File_Unzipper($regularContents);
		$this->assertEquals($regularContents, $unzipper->getUnpacked());
	}

	public function testShouldReadZippedFile() {
		$zippedContents = file_get_contents(GARP_APPLICATION_PATH . self::ZIPPED_FILE);
		$unzipper = new Garp_File_Unzipper($zippedContents);
		$this->assertEquals(self::ZIPPED_CONTENT, $unzipper->getUnpacked());
	}

	public function testShouldReadDoublyZippedFile() {
		$doubleZippedContents = file_get_contents(GARP_APPLICATION_PATH . self::DOUBLE_ZIPPED_FILE);
		$unzipper = new Garp_File_Unzipper($doubleZippedContents);
		$this->assertEquals(self::DOUBLE_ZIPPED_CONTENT, $unzipper->getUnpacked());
	}
}
